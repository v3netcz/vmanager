For running framework tests please run shell script: ./run-tests.sh
(Sorry UNIX only)

If you want to run only some tests use:
For application tests only (default)
./run-tests.sh app

For framework tests only:
./run-tests.sh fw

For running all tests:
./run-tests.sh all

---------------------

Please add your application tests into AppTests (or any subdirectory). Tests
need to have .phpt extension.

You can use Nette test framework when creating new tests. Sample usage can be
seen in AppTests/SampleTest.phpt