@echo off
title CDSS DBD - Ngrok Tunnel
color 0A
cls

echo ============================================
echo   CDSS DBD - Ngrok Public URL Launcher
echo ============================================
echo.

REM Cek apakah ngrok.exe ada
if not exist "%~dp0ngrok.exe" (
    echo [ERROR] ngrok.exe tidak ditemukan di folder ini!
    echo Pastikan ngrok.exe ada di: %~dp0
    pause
    exit /b 1
)

echo [OK] Memulai ngrok tunnel ke http://localhost:85
echo.
echo Setelah tunnel aktif:
echo   - Salin URL dari baris "Forwarding"
echo   - Tambahkan /spk/ di akhir URL
echo   - Contoh: https://xxxx.ngrok-free.app/spk/
echo.
echo ============================================
echo PENTING: Jangan tutup jendela ini!
echo ============================================
echo.

"%~dp0ngrok.exe" http 85
pause
