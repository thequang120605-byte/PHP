@echo off
setlocal
cd /d "%~dp0"
where php >nul 2>nul
if errorlevel 1 (
 echo Hay mo Laragon Terminal de nhan dung PHP roi chay lai.
 pause
 exit /b 1
)
if not exist .env copy .env.example .env >nul
if not exist vendor\autoload.php (
 call composer install --prefer-dist --no-interaction
 if errorlevel 1 goto fail
)
php artisan config:clear
if errorlevel 1 goto fail
php -r "$v=parse_ini_file('.env'); exit(empty($v['APP_KEY'])?1:0);"
if errorlevel 1 php artisan key:generate
echo.
echo Tao database huit_events va sua thong tin MySQL trong .env.
echo Sau do chay cac lenh:
echo php artisan migrate --seed
echo php artisan storage:link
echo php artisan serve
echo.
echo Doc README_VI.md de xem huong dan va tai khoan demo.
pause
exit /b 0
:fail
echo Co loi. Kiem tra PHP, extension, Composer va thong bao phia tren.
pause
exit /b 1
