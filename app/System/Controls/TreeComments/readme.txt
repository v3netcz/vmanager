--- Tree Comments doc ---
1. Run app/Wiki/dbstructure.sql if you haven't already done so.
   You may change the table names but remember to configure the controls accordingly.

2. In your presenter
	public function createComponentTreeComments() {
		$c = new Controls\TreeComments();

		// settings
		$c->model->setTable('wiki_discussion') // see step one
			->addCondition('article_id = %i', $this->getArticle($this->getParam('wikiId'))->id);
		
		return $c;
	}
	
	The columns are by default expected to be:
		DEFAULT_FIELD_ID	= 'id';
		DEFAULT_FIELD_LFT	= 'lft';
		DEFAULT_FIELD_RGT	= 'rgt';
		DEFAULT_FIELD_LEVEL = 'level';
	
	
3. In your template
	{control treeComments}
	for a normal default conventional render.

	{control treeComments tree}
	<strong>Any fancy stuff...</strong>
	{control treeComments form}
	for a non-conventional render.

	You can create your own renderer. Just implement vManager\Modules\Wiki\Controls\IRenderer
	and then register it using the TreeComments::setRenderer() method.

