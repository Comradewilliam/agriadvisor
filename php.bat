@echo off
REM Use MAMP PHP with pdo_mysql enabled (fixes "could not find driver")
set PHP=C:\MAMP\bin\php\php8.3.1\php.exe
set INI=C:\MAMP\conf\php8.3.1\php.ini
"%PHP%" -c "%INI%" %*
