@echo off

rem Usage: TEST [included_group ...] [- [excluded_group ...]]

set XDEBUG_CONFIG="idekey=xxx"

set _gi=
set _ge=

:inc
if "%1" == "" goto run
if "%1" == "-" goto exc
set _gi=%_gi% --group %1
shift
goto inc

:exc
shift
if "%1" == "" goto run
set _ge=%_ge% --exclude-group %1
goto exc

:run
phpunit --bootstrap ..\bootstrap.php -d iconv.internal_encoding=utf-8 -d iconv.output_encoding=cp866 -v%_gi%%_ge% .
set _gi=
set _ge=
echo.