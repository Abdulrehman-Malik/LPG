; Perfect LPG - Windows client installer (Inno Setup 6). Build it with installer\build.ps1.
; setup.exe carries only the application. On the client PC it looks up and downloads the latest
; Apache 2.4, PHP 8.5 (TS x64), MySQL 8.4 LTS and VC++ runtime (needs internet), sets them up as
; services and opens the app's Installation page to create the database.
; Running setup.exe again upgrades the components; the database, config\ and app\.env are kept.

#ifndef AppVersion
  #define AppVersion "1.0.0"
#endif
#ifndef Stage
  #define Stage ".stage"
#endif

[Setup]
AppId={{B7E1C2D4-5F60-4A8B-9C3D-2E4F6A8B0C1D}
AppName=Perfect LPG
AppVersion={#AppVersion}
AppVerName=Perfect LPG {#AppVersion}
AppPublisher=Perfect LPG
DefaultDirName={sd}\PerfectLPG
UsePreviousAppDir=yes
DisableProgramGroupPage=yes
PrivilegesRequired=admin
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
MinVersion=10.0
OutputDir=output
OutputBaseFilename=PerfectLPG-Setup-{#AppVersion}
SetupIconFile={#Stage}\app.ico
UninstallDisplayIcon={app}\installer\app.ico
UninstallDisplayName=Perfect LPG
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
SetupLogging=yes
CloseApplications=no

[Tasks]
Name: firewall; Description: "Allow other computers on this network (POS counters) to open Perfect LPG"; Flags: unchecked

[InstallDelete]
; Replaced on every install/upgrade (apache, php and mysql are re-extracted from the downloads).
; data\, backups\, config\, logs\, app\.env and app\writable\ are kept.
Type: filesandordirs; Name: "{app}\apache"
Type: filesandordirs; Name: "{app}\php"
Type: filesandordirs; Name: "{app}\mysql"
Type: filesandordirs; Name: "{app}\installer"
Type: filesandordirs; Name: "{app}\app\app"
Type: filesandordirs; Name: "{app}\app\system"
Type: filesandordirs; Name: "{app}\app\vendor"
Type: filesandordirs; Name: "{app}\app\public"
Type: filesandordirs; Name: "{app}\app\database"

[Files]
Source: "{#Stage}\app\*"; DestDir: "{app}\app"; Excludes: "\writable\*"; Flags: ignoreversion recursesubdirs createallsubdirs
Source: "{#Stage}\app\writable\*"; DestDir: "{app}\app\writable"; Flags: onlyifdoesntexist uninsneveruninstall recursesubdirs createallsubdirs
Source: "scripts\configure.ps1"; DestDir: "{app}\installer"; Flags: ignoreversion
Source: "scripts\backup.ps1"; DestDir: "{app}\installer"; Flags: ignoreversion
Source: "scripts\uninstall.ps1"; DestDir: "{app}\installer"; Flags: ignoreversion
Source: "{#Stage}\app.ico"; DestDir: "{app}\installer"; Flags: ignoreversion
Source: "scripts\resolve.ps1"; Flags: dontcopy
Source: "scripts\port-check.ps1"; Flags: dontcopy

[UninstallRun]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\installer\uninstall.ps1"" -InstallDir ""{app}"""; Flags: runhidden waituntilterminated; RunOnceId: "RemoveServices"

[UninstallDelete]
Type: filesandordirs; Name: "{app}\apache"
Type: filesandordirs; Name: "{app}\php"
Type: filesandordirs; Name: "{app}\mysql"
Type: filesandordirs; Name: "{app}\installer"
Type: filesandordirs; Name: "{app}\app\app"
Type: filesandordirs; Name: "{app}\app\system"
Type: filesandordirs; Name: "{app}\app\vendor"
Type: filesandordirs; Name: "{app}\app\public"
Type: filesandordirs; Name: "{app}\app\database"

[Run]
Filename: "{code:GetAppUrl}"; Description: "Open Perfect LPG"; Flags: postinstall shellexec nowait; Check: ConfigureSucceeded

[Code]
const
  Components = 'php,apache,mysql,vcredist,cacert';

var
  ServerPage: TInputQueryWizardPage;
  DownloadPage: TDownloadWizardPage;
  ConfigureOk: Boolean;

function InstallIni(): String;
begin
  Result := ExpandConstant('{app}\config\install.ini');
end;

function ComponentsIni(): String;
begin
  Result := ExpandConstant('{tmp}\components.ini');
end;

function HttpPort(): String;
begin
  Result := Trim(ServerPage.Values[0]);
  if Result = '' then
  begin
    Result := '80';
    if FileExists(InstallIni) then
      Result := GetIniString('install', 'http_port', '80', InstallIni);
  end;
end;

function RunScript(Script, Args: String): Boolean;
var
  Code: Integer;
begin
  ExtractTemporaryFile(Script);
  Result := Exec('powershell.exe', '-NoProfile -ExecutionPolicy Bypass -File "' + ExpandConstant('{tmp}\') + Script + '" ' + Args,
    '', SW_HIDE, ewWaitUntilTerminated, Code) and (Code = 0);
end;

procedure InitializeWizard;
begin
  ServerPage := CreateInputQueryPage(wpSelectDir, 'Server settings', 'Web port and company name',
    'Perfect LPG runs its own web server and database on this computer. The database and the admin account are created on the Installation page that opens at the end.');
  ServerPage.Add('Web port (80 is standard; use 8080 if 80 is busy):', False);
  ServerPage.Add('Company name shown on the login page (optional):', False);
  ServerPage.Values[0] := ExpandConstant('{param:HTTPPORT|}');
  ServerPage.Values[1] := ExpandConstant('{param:COMPANY|}');
  DownloadPage := CreateDownloadPage('Downloading server components', 'Apache, PHP, MySQL and the Microsoft VC++ runtime (about 300 MB).', nil);
end;

procedure CurPageChanged(CurPageID: Integer);
begin
  if CurPageID = ServerPage.ID then
    ServerPage.Values[0] := HttpPort;
end;

// Finds the latest component versions and downloads them into {tmp}, checking SHA-256 where the vendor publishes one.
function DownloadComponents(): Boolean;
var
  Names: TStringList;
  I: Integer;
begin
  Result := False;
  DownloadPage.SetText('Finding the latest versions...', '');
  DownloadPage.SetProgress(0, 0);
  DownloadPage.Show;
  try
    if not RunScript('resolve.ps1', '-OutFile "' + ComponentsIni + '"') then
    begin
      SuppressibleMsgBox('Could not look up the server components. Check the internet connection and try again.' + #13#10#13#10 +
        GetIniString('error', 'message', '', ComponentsIni), mbError, MB_OK, IDOK);
      Exit;
    end;
    DownloadPage.Clear;
    Names := TStringList.Create;
    try
      Names.CommaText := Components;
      for I := 0 to Names.Count - 1 do
        DownloadPage.Add(GetIniString(Names[I], 'url', '', ComponentsIni), GetIniString(Names[I], 'file', '', ComponentsIni),
          GetIniString(Names[I], 'sha256', '', ComponentsIni));
    finally
      Names.Free;
    end;
    try
      DownloadPage.Download;
      Result := True;
    except
      if not DownloadPage.AbortedByUser then
        SuppressibleMsgBox('Download failed: ' + AddPeriod(GetExceptionMessage) + #13#10#13#10 +
          'Check the internet connection and click Install again.', mbError, MB_OK, IDOK);
    end;
  finally
    DownloadPage.Hide;
  end;
end;

function NextButtonClick(CurPageID: Integer): Boolean;
var
  Port: Integer;
begin
  Result := True;
  if (CurPageID = wpSelectDir) and (Pos(' ', WizardDirValue) > 0) then
  begin
    MsgBox('Please choose a folder without spaces, for example C:\PerfectLPG.', mbError, MB_OK);
    Result := False;
  end
  else if CurPageID = ServerPage.ID then
  begin
    Port := StrToIntDef(HttpPort, 0);
    if (Port < 1) or (Port > 65535) then
    begin
      MsgBox('Enter a web port between 1 and 65535.', mbError, MB_OK);
      Result := False;
    end
    else if not RunScript('port-check.ps1', '-Port ' + HttpPort + ' -InstallDir "' + ExpandConstant('{app}') + '"') then
    begin
      MsgBox('Port ' + HttpPort + ' is already used by another program (often IIS, Skype or XAMPP). Enter a different port, such as 8080.', mbError, MB_OK);
      Result := False;
    end;
  end
  else if CurPageID = wpReady then
    Result := DownloadComponents;
end;

// The running services lock the Apache, PHP and MySQL files that an upgrade replaces.
function PrepareToInstall(var NeedsRestart: Boolean): String;
var
  Code: Integer;
begin
  Result := '';
  Exec(ExpandConstant('{sys}\net.exe'), 'stop PerfectLPG-Apache', '', SW_HIDE, ewWaitUntilTerminated, Code);
  Exec(ExpandConstant('{sys}\net.exe'), 'stop PerfectLPG-MySQL', '', SW_HIDE, ewWaitUntilTerminated, Code);
end;

procedure CurStepChanged(CurStep: TSetupStep);
var
  Code: Integer;
  Args, Company: String;
begin
  if CurStep <> ssPostInstall then
    Exit;

  Company := Trim(ServerPage.Values[1]);
  StringChangeEx(Company, '"', '', True);
  Args := '-NoProfile -ExecutionPolicy Bypass -File "' + ExpandConstant('{app}\installer\configure.ps1') + '"' +
    ' -InstallDir "' + ExpandConstant('{app}') + '" -DownloadDir "' + ExpandConstant('{tmp}') + '"' +
    ' -HttpPort ' + HttpPort + ' -CompanyName "' + Company + '"';
  if WizardIsTaskSelected('firewall') then
    Args := Args + ' -OpenFirewall';

  WizardForm.StatusLabel.Caption := 'Setting up MySQL, PHP and Apache (this can take a few minutes)...';
  WizardForm.ProgressGauge.Style := npbstMarquee;
  try
    ConfigureOk := Exec('powershell.exe', Args, '', SW_HIDE, ewWaitUntilTerminated, Code) and (Code = 0);
  finally
    WizardForm.ProgressGauge.Style := npbstNormal;
  end;
  if not ConfigureOk then
    SuppressibleMsgBox('The files were installed, but setting up the database or web server failed.' + #13#10#13#10 +
      'Details: the newest install-*.log in ' + ExpandConstant('{app}\logs') + #13#10#13#10 +
      'Fix the problem and run setup again.', mbError, MB_OK, IDOK);
end;

function ConfigureSucceeded(): Boolean;
begin
  Result := ConfigureOk;
end;

function GetAppUrl(Param: String): String;
begin
  Result := GetIniString('install', 'url', 'http://localhost:' + HttpPort + '/', InstallIni);
end;
