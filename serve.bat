@echo off
cd /d "%~dp0"
REM Stop stale PHP servers on 1234 (fixes broken CSS from old processes)
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":1234.*LISTENING"') do taskkill /F /PID %%a >nul 2>&1
echo Starting Agri-Advisory at http://127.0.0.1:1234
echo Use 127.0.0.1:1234 in the browser (not localhost if styles break)
call php.bat -S 127.0.0.1:1234 -t public public/router.php
