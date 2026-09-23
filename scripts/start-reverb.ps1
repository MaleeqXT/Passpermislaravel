$ErrorActionPreference = 'Stop'

$hostName = '127.0.0.1'
$port = 8080

function Test-ReverbPort {
    try {
        $client = [System.Net.Sockets.TcpClient]::new()
        $client.Connect($hostName, $port)
        $client.Dispose()
        return $true
    } catch {
        return $false
    }
}

if (Test-ReverbPort) {
    exit 0
}

$php = (Get-Command php -ErrorAction Stop).Source
$project = Split-Path -Parent $PSScriptRoot
$logDirectory = Join-Path $project 'storage\\logs'

Start-Process -FilePath $php `
    -ArgumentList 'artisan', 'reverb:start', '--host=127.0.0.1', '--port=8080' `
    -WorkingDirectory $project `
    -WindowStyle Hidden `
    -RedirectStandardOutput (Join-Path $logDirectory 'reverb-server.log') `
    -RedirectStandardError (Join-Path $logDirectory 'reverb-server-error.log')

for ($attempt = 0; $attempt -lt 20; $attempt++) {
    Start-Sleep -Milliseconds 250
    if (Test-ReverbPort) {
        exit 0
    }
}

throw 'Reverb could not start on 127.0.0.1:8080. See storage/logs/reverb-server-error.log.'
