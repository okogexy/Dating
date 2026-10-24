$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
if (-not (Test-Path -LiteralPath 'vendor/autoload.php')) {
    $env:COMPOSER_IPRESOLVE = '4'
    composer install --no-interaction --prefer-dist
    if ($LASTEXITCODE -ne 0) { throw 'Не удалось установить PHP-зависимости.' }
}
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
    php artisan key:generate
}
$databasePath = (Join-Path $PSScriptRoot 'database/svyaz.sqlite').Replace('\', '/')
$environment = Get-Content -LiteralPath '.env' -Raw
if ($environment -match '(?m)^DB_DATABASE=') {
    $environment = [regex]::Replace($environment, '(?m)^DB_DATABASE=.*$', 'DB_DATABASE="' + $databasePath + '"')
} else {
    $environment += "`nDB_DATABASE=`"$databasePath`"`n"
}
[System.IO.File]::WriteAllText((Join-Path $PSScriptRoot '.env'), $environment, (New-Object System.Text.UTF8Encoding $false))
if (-not (Test-Path -LiteralPath $databasePath)) { New-Item -ItemType File -Path $databasePath | Out-Null }
php artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Не удалось подготовить базу данных.' }
php artisan db:seed --class=DatingSeeder --force
if (-not (Test-Path -LiteralPath 'public/storage')) { php artisan storage:link }
if (-not (Test-Path -LiteralPath 'public/build/manifest.json')) {
    npm install
    if ($LASTEXITCODE -ne 0) { throw 'Не удалось установить frontend-зависимости.' }
    npm run build
    if ($LASTEXITCODE -ne 0) { throw 'Не удалось собрать frontend.' }
}
Write-Host 'Связь: http://127.0.0.1:8000'
php artisan serve --host=127.0.0.1 --port=8000
