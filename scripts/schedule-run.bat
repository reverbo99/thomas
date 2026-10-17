@echo off
REM ---------------------------------------------------------------------------
REM Runs the Laravel scheduler once (Windows equivalent of a cron tick).
REM Register this file with Windows Task Scheduler to run every 1 minute.
REM ---------------------------------------------------------------------------

cd /d "%~dp0.."

set "PHP=E:\xamp\php\php.exe"
if not exist "%PHP%" set "PHP=php"

if not exist "storage\logs" mkdir "storage\logs"

"%PHP%" artisan schedule:run >> "storage\logs\scheduler.log" 2>&1
