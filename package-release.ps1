# ==============================================================================
# HUIT EventPass - Script đóng gói bản phát hành sạch (Clean Release Packager)
# ==============================================================================
# Mục đích:
# Loại bỏ hoàn toàn các tệp nhạy cảm (secrets, runtime sessions, logs, cache, .git)
# trước khi nén thành tệp ZIP bàn giao hoặc triển khai.
# ==============================================================================

param(
    [string]$OutputZip = "..\HuitEventPass_Production_Release.zip"
)

Write-Host ">>> Bắt đầu chuẩn bị gói phát hành sạch..." -ForegroundColor Cyan

$tempDir = Join-Path $env:TEMP ("HuitEventPass_Clean_" + [System.Guid]::NewGuid().ToString().Substring(0, 8))
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

$excludeItems = @(
    ".git",
    ".github",
    ".env",
    "storage\framework\sessions\*",
    "storage\framework\views\*",
    "storage\framework\cache\data\*",
    "storage\logs\*.log",
    "bootstrap\cache\config.php",
    "bootstrap\cache\routes-v7.php",
    "bootstrap\cache\services.php",
    "bootstrap\cache\packages.php",
    "node_modules",
    ".phpunit.result.cache"
)

Write-Host "1. Sao chép cây thư mục dự án sang thư mục tạm: $tempDir"
$sourceDir = Get-Location

# Copy tất cả ngoại trừ .git và node_modules
robocopy $sourceDir $tempDir /E /XD .git node_modules /XF .env *.log /NDL /NFL /NJH /NJS

# Đảm bảo các thư mục rỗng có file .gitignore
$keepDirs = @(
    "storage\app\private",
    "storage\app\public",
    "storage\framework\cache",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs"
)

foreach ($dir in $keepDirs) {
    $targetPath = Join-Path $tempDir $dir
    if (!(Test-Path $targetPath)) {
        New-Item -ItemType Directory -Path $targetPath -Force | Out-Null
    }
    # Dọn sạch các file runtime nếu có lọt vào
    Get-ChildItem -Path $targetPath -File -Recurse | Where-Object { $_.Name -ne ".gitignore" } | Remove-Item -Force -ErrorAction SilentlyContinue
    $gitIgnore = Join-Path $targetPath ".gitignore"
    if (!(Test-Path $gitIgnore)) {
        Set-Content -Path $gitIgnore -Value "*`n!.gitignore`n" -Encoding UTF8
    }
}

# Dọn dẹp bootstrap/cache
$bootstrapCache = Join-Path $tempDir "bootstrap\cache"
Get-ChildItem -Path $bootstrapCache -File | Where-Object { $_.Name -ne ".gitignore" } | Remove-Item -Force -ErrorAction SilentlyContinue

# Kiểm tra đảm bảo có .env.example
if (Test-Path ".env.example") {
    Copy-Item ".env.example" (Join-Path $tempDir ".env.example") -Force
}

# Xóa ZIP cũ nếu tồn tại
if (Test-Path $OutputZip) {
    Remove-Item $OutputZip -Force
}

Write-Host "2. Nén toàn bộ mã nguồn sạch thành tệp ZIP: $OutputZip"
Compress-Archive -Path "$tempDir\*" -DestinationPath $OutputZip -Force

# Dọn dẹp thư mục tạm
Remove-Item -Recurse -Force $tempDir

Write-Host "✔ HOÀN TẤT ĐÓNG GÓI! Gói phát hành đã được lưu tại: $OutputZip" -ForegroundColor Green
Write-Host "✔ Đã loại trừ: .env, APP_KEY, session login, file log, cache và lịch sử git." -ForegroundColor Green
