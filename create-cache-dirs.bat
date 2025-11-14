@echo off
REM Create required directories on VPS

echo Creating cache directories...
cd C:\inetpub\wwwroot\bkuteam.site\php

mkdir cache 2>nul
mkdir cache\rate-limits 2>nul

echo Setting permissions...
icacls cache /grant "IIS AppPool\bkuteam:(OI)(CI)F" /T

echo.
echo Testing directory creation...
dir cache

echo.
echo Done!
pause
