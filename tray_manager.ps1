Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

$projectDir = "e:\Projects\Garage-crm"
$global:serverPids = @()

function Start-App {
    if ($global:serverPids.Count -gt 0) {
        $global:notifyIcon.BalloonTipText = "Servers are already running!"
        $global:notifyIcon.ShowBalloonTip(2000)
        return
    }

    $phpProc = Start-Process -FilePath "e:\Projects\Garage-crm\tools\php\php.exe" -ArgumentList "artisan serve" -WorkingDirectory $projectDir -WindowStyle Hidden -PassThru
    $npmProc = Start-Process -FilePath "cmd.exe" -ArgumentList "/c npm run dev" -WorkingDirectory $projectDir -WindowStyle Hidden -PassThru
    $waProc = Start-Process -FilePath "cmd.exe" -ArgumentList "/c npm run start" -WorkingDirectory "$projectDir\whatsapp-server" -WindowStyle Hidden -PassThru
    
    $global:serverPids += $phpProc.Id
    $global:serverPids += $npmProc.Id
    $global:serverPids += $waProc.Id
    
    $global:notifyIcon.BalloonTipText = "Garage CRM & OpenWA Started"
    $global:notifyIcon.ShowBalloonTip(2000)
}

function Stop-App {
    if ($global:serverPids.Count -eq 0) {
        return
    }

    foreach ($pidNum in $global:serverPids) {
        if ($pidNum) {
            Start-Process "taskkill.exe" -ArgumentList "/F /T /PID $pidNum" -WindowStyle Hidden -Wait
        }
    }
    $global:serverPids = @()
    
    $global:notifyIcon.BalloonTipText = "Garage CRM Stopped"
    $global:notifyIcon.ShowBalloonTip(2000)
}

function Restart-App {
    Stop-App
    Start-Sleep -Seconds 1
    Start-App
}

$contextMenu = New-Object System.Windows.Forms.ContextMenu

$accessItem = New-Object System.Windows.Forms.MenuItem("Access CRM")
$accessItem.add_Click({ Start-Process "http://localhost:8000" })

$startItem = New-Object System.Windows.Forms.MenuItem("Start App")
$startItem.add_Click({ Start-App })

$stopItem = New-Object System.Windows.Forms.MenuItem("Stop App")
$stopItem.add_Click({ Stop-App })

$restartItem = New-Object System.Windows.Forms.MenuItem("Restart App")
$restartItem.add_Click({ Restart-App })

$exitItem = New-Object System.Windows.Forms.MenuItem("Exit")
$exitItem.add_Click({
    Stop-App
    $global:notifyIcon.Visible = $false
    [System.Windows.Forms.Application]::Exit()
})

$contextMenu.MenuItems.Add($accessItem)
$contextMenu.MenuItems.Add($startItem)
$contextMenu.MenuItems.Add($stopItem)
$contextMenu.MenuItems.Add($restartItem)
$contextMenu.MenuItems.Add("-")
$contextMenu.MenuItems.Add($exitItem)

$global:notifyIcon = New-Object System.Windows.Forms.NotifyIcon
$global:notifyIcon.Icon = [System.Drawing.SystemIcons]::Application
$global:notifyIcon.ContextMenu = $contextMenu
$global:notifyIcon.Visible = $true
$global:notifyIcon.Text = "Garage CRM Manager"
$global:notifyIcon.BalloonTipTitle = "Garage CRM"
$global:notifyIcon.BalloonTipText = "Manager is running in the background. Right-click to start servers."
$global:notifyIcon.ShowBalloonTip(3000)

[System.Windows.Forms.Application]::Run()