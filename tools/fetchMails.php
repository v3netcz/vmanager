<?php
use Nette\Utils\Strings;

include __DIR__ . '/bootstrap.php';

$moduleInstance = vManager\Modules\Accounting::getInstance();
if(!$moduleInstance->isEnabled()) die("Module not enabled");

$config = $moduleInstance->getConfig();
if(!isset($config['bankMailDownloader'])) die("Missing notificationMailDownloader config option");
else $config = $config['bankMailDownloader'];
if(!isset($config->imapUrl)) die("Missing bankMailDownloader->imapUrl config option");
if(!isset($config->user)) die("Missing bankMailDownloader->user config option");
if(!isset($config->password)) die("Missing bankMailDownloader->password config option");
if(!isset($config->ourBankAccount)) die("Missing bankMailDownloader->ourBankAccount config option");

// -----------------------------------------------------------------------------

$searchString = 'FROM "info@kb.cz"';

$ids = $context->database->connection->query("SELECT [bankId] FROM [accounting_bankTransactions]")->fetchPairs('bankId', 'bankId');
$since = $context->database->connection->query("SELECT MAX([date]) FROM [accounting_bankTransactions]")->fetchSingle();

if($since) {
	$since = $since->sub(\DateInterval::createFromDateString('1 day'));
	$searchString .= ' SINCE "' . $since->format('Y-m-d') . '"';
}

$inbox = imap_open($config->imapUrl, $config->user, $config->password) or die('Cannot connect to IMAP server: ' . imap_last_error());
$messages = imap_search($inbox, $searchString);

if($messages) {

	foreach($messages as $msgNo) {

		echo "Processing message no. $msgNo\n";
		flush();

		$overview = imap_fetch_overview($inbox, $msgNo, 0);
		$ok = false;

		if(Strings::startsWith($overview[0]->subject, '=?')) {
			//$decoded = imap_mime_header_decode($overview[0]->subject);
			//$subject = $decoded[0]->text;
			
			$subject = iconv('UTF-8', 'ASCII//TRANSLIT', imap_utf8($overview[0]->subject));
		} else
			$subject = $overview[0]->subject;

		if(preg_match('/Oznameni ID: ([0-9]+)/', $subject, $matches)) {

			$notificationNo = (int) $matches[1];
			
			if(isset($ids[$notificationNo])) {
				$ok = true;
				continue;
			}			

			if($notificationNo > 0) {

				$structure = imap_fetchstructure($inbox, $msgNo);

				// TODO: Ty casti (a kodovani by mely byt dynamicke v zavislosti na $structure)
				$textBody = imap_fetchbody($inbox, $msgNo, 1);				
				if($structure->parts[0]->encoding == 3) $textBody = iconv('windows-1250', 'ASCII//TRANSLIT', base64_decode($textBody));

				if(preg_match('/Oznamujeme Vam provedeni platby z uctu cislo ([^\\s]+) na ucet cislo ([^\\s]+) castka (.+?) CZK .+? datum splatnosti ([^\\s]+).+? variabilni symbol platby ([0-9]+)/', $textBody, $matches)) {
					$ok = true;

					$iData = array(
						'bankId' => $notificationNo,
						'date' => date('Y-m-d', strtotime($matches[4])),
						'fromAccount' => $matches[1],
						'toAccount' => $matches[2],
						'amount' => floatval(preg_replace(array('/\\s+/', '/,/'), array('', '.'), $matches[3])),
						'varSymbol' => $matches[5]
					);
					
					$context->database->connection->query("INSERT INTO [accounting_bankTransactions]", $iData);

					// Attachment
					// file_put_contents(__DIR__ . '/oznameni.pdf', base64_decode(imap_fetchbody($inbox, $msgNo, 2)));
				} else {
					var_dump($structure);
					var_dump($textBody);
				}
			}
		}

		if(!$ok) {
			echo "Cannot parse received message\n\n";
		
			$body = "Subject: " . var_export($subject, true) . "\n\n";
			$body .= "Headers:\n";
			$body .= var_export($overview[0], true);

			$mail = new Nette\Mail\Message;
			$mail->setFrom('info@v3net.cz');
			$mail->addTo('adam.stanek@v3net.cz');
			$mail->setSubject("Chyba pri parsovani bankovniho e-mailu");
			$mail->setBody($body);
			$mail->send();
		
			exit;
		}
	}

}

imap_close($inbox);

// -----------------------------------------------------------------------------

$unpairedTransactions = $context->database->connection->query("SELECT * FROM [accounting_bankTransactions] WHERE [recordId] IS NULL")->fetchAll();
$nextIds = array();

foreach($unpairedTransactions as $transaction) {
	
	echo "Processing bank transaction no. " . $transaction->bankId . ":\n";
	
	$recordData = array(
		'date' => $transaction->date->format('Y-m-d'),
		'value' => $transaction->amount,
	);

	if($transaction->fromAccount == $config->ourBankAccount) {
		$type = 'BV';
		$recordData['d'] = 221001;
		
		// Vyplata
		if($transaction->toAccount == '107-1497410287/0100') {
			$recordData['description'] = 'Výplata Adam';
		}
		
		// Platba zálohové daně
		else if($transaction->toAccount == '713-7622101/0710' && $transaction->varSymbol == 24192007) {
			$recordData['description'] = 'Zálohová daň';
		}
		
		else if($transaction->toAccount == '35-9039020237/0100' && $transaction->varSymbol == 93403) {
			$recordData['description'] = 'Internet - úhrada paušálního poplatku';
		}
		
	} else if($transaction->toAccount == $config->ourBankAccount) {
		$type = 'BP';
		$recordData['md'] = 221001;
		
		// Vracene bankovni poplatky
		if($transaction->fromAccount == '43-6964420277/0100') {
			$recordData['description'] = 'Bonus za vedení účtu';
		}
		
		// Prijate platby za nase faktury
		else if(mb_strlen($transaction->varSymbol) == 8) {
			if(abs(mb_substr($transaction->varSymbol, 0, 4) - $transaction->date->format('Y')) <= 1) {
				$recordData['subjectEvidenceId'] = $transaction->varSymbol;
				$recordData['description'] = 'FA ' . mb_substr($transaction->varSymbol, 0, 4) . '/' . mb_substr($transaction->varSymbol, 4);
			}
		}
	}
	
	$ds = $context->database->connection->select('[id]')->from('[accounting_records]')
				->where('[date] = %s', $recordData['date'])
				->and('[value] = %f', $recordData['value']);
				
	if(isset($recordData['d'])) $ds->and('[d] = %s', $recordData['d']);
	if(isset($recordData['md'])) $ds->and('[md] = %s', $recordData['md']);
	
	$id = $ds->fetch();

	$context->database->connection->begin();

	// Zaznam neexistuje
	if($id === FALSE) {
		echo "\t- Accounting record not found, creating new one\n";
		foreach($recordData as $k => $v) {
			echo "\t- " . ucfirst($k) . ": " . $v . "\n";
		}
	
		if(!isset($nextIds[$transaction->date->format('Y')][$type])) {
			$nextIds[$transaction->date->format('Y')][$type] = (int) $context->database->connection->query("SELECT COALESCE(MAX(CAST(SUBSTRING([evidenceId], -3) AS UNSIGNED)), 0) FROM [accounting_records] WHERE [evidenceId] REGEXP %s", $transaction->date->format('y') . ' ?' . $type)->fetchSingle();	
		}
		
		// Nakonec tam ty IDcka nemuzem davat, protoze v tom nejsou napr. platby kartou => nebyla by rozumna rada (jedine ji oddelit do budoucna?)
		// $recordData['evidenceId'] = $transaction->date->format('y') . $type . str_pad(++$nextIds[$transaction->date->format('Y')][$type], 3, "0", STR_PAD_LEFT);			
		
		$context->database->connection->query("INSERT INTO [accounting_records]", $recordData);
		$id = $context->database->connection->getInsertId();		
	} else {
		echo "\t- Accounting record already exists, linking it to the bank transaction\n";
		echo "\t- Record ID: $id->id\n";
		// if(!empty($id->evidenceId)) echo "\t- Evidence ID: $id->evidenceId\n";
		
		$id = $id->id;
	}
	
	$context->database->connection->query("UPDATE [accounting_bankTransactions] SET [recordId] = %i", $id, "WHERE [bankId] = %i", $transaction->bankId);
	
	// $context->database->connection->rollback();
	$context->database->connection->commit();
	
	echo "\n";
}


