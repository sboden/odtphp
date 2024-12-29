@echo off

rem Assuming you're on Windows.
rem Assuming you have php.exe in your path
rem
rem Add option "--debug" to get more output.

php.exe phpunit-runner.php --configuration=phpunit.xml
