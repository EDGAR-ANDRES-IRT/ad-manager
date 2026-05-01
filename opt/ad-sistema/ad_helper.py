import winrm
import json
import os

class ADManager:
    def __init__(self):
        self.host     = os.getenv("AD_HOST",     "http://172.16.10.2:5985/wsman")
        self.user     = os.getenv("AD_USER",     "Administrador")
        self.password = os.getenv("AD_PASSWORD", "Eaha823")
        self.session  = winrm.Session(
            self.host,
            auth=(self.user, self.password),
            transport="ntlm",
            server_cert_validation="ignore"
        )

    # ------------------------------------------------------------------
    # HELPER INTERNO: ejecutar PowerShell y parsear JSON
    # ------------------------------------------------------------------
    def _run(self, ps_script: str):
        """Ejecuta un script PowerShell y retorna la salida como texto."""
        r = self.session.run_ps(ps_script)
        if r.status_code != 0:
            err = r.std_err.decode("utf-8", errors="replace").strip()
            raise Exception(f"PowerShell error: {err}")
        return r.std_out

    def _execute(self, comando: str):
        """Ejecuta un comando PS, agrega ConvertTo-Json y parsea la salida."""
        cmd = f"Import-Module ActiveDirectory; {comando} | ConvertTo-Json -Depth 5 -Compress"
        raw = self._run(cmd)
        if not raw:
            return []
        try:
            text = raw.decode("cp1252")
        except (UnicodeDecodeError, LookupError):
            text = raw.decode("utf-8", errors="replace")
        text = text.strip()
        if not text:
            return []
        data = json.loads(text)
        # Si PowerShell devuelve un objeto único (no lista), lo envolvemos
        if isinstance(data, dict):
            return [data]
        return data

    def _execute_no_json(self, ps_script: str) -> str:
        """Ejecuta PS sin parsear JSON — para operaciones de escritura."""
        cmd = f"Import-Module ActiveDirectory; {ps_script}"
        raw = self._run(cmd)
        return raw.decode("utf-8", errors="replace").strip()

    # ------------------------------------------------------------------
    # DOMINIO
    # ------------------------------------------------------------------
    def get_domain_info(self):
        return self._execute(
            "Get-ADDomain | Select-Object Name, NetBIOSName, DNSRoot, "
            "Forest, PDCEmulator, DomainMode, InfrastructureMaster"
        )

    def get_domain_stats(self):
        ps = """
Import-Module ActiveDirectory
$u  = (Get-ADUser     -Filter *).Count
$g  = (Get-ADGroup    -Filter *).Count
$c  = (Get-ADComputer -Filter *).Count
$ou = (Get-ADOrganizationalUnit -Filter *).Count
$d  = (Get-ADUser -Filter {Enabled -eq $false}).Count
@{Users=$u; Groups=$g; Computers=$c; OUs=$ou; DisabledUsers=$d} | ConvertTo-Json -Compress
"""
        raw = self._run(ps)
        return json.loads(raw.decode("utf-8", errors="replace"))

    # ------------------------------------------------------------------
    # UNIDADES ORGANIZATIVAS (OU)
    # ------------------------------------------------------------------
    def get_ous(self):
        return self._execute(
            "Get-ADOrganizationalUnit -Filter * "
            "-Properties Name, DistinguishedName, Description"
            " | Select-Object Name, DistinguishedName, Description"
        )

    def create_ou(self, name: str, path: str, description: str = ""):
        ps = (
            f"New-ADOrganizationalUnit "
            f"-Name '{name}' "
            f"-Path '{path}' "
            f"-Description '{description}' "
            f"-ProtectedFromAccidentalDeletion $false"
        )
        self._execute_no_json(ps)
        return {"ok": True}

    def delete_ou(self, dn: str):
        ps = (
            f"Set-ADOrganizationalUnit -Identity '{dn}' "
            f"-ProtectedFromAccidentalDeletion $false; "
            f"Remove-ADOrganizationalUnit -Identity '{dn}' "
            f"-Recursive -Confirm:$false"
        )
        self._execute_no_json(ps)
        return {"ok": True}

    # ------------------------------------------------------------------
    # USUARIOS
    # ------------------------------------------------------------------
    def get_users(self, ou_dn: str = ""):
        base = f"-SearchBase '{ou_dn}'" if ou_dn else ""
        return self._execute(
            f"Get-ADUser -Filter * {base} "
            f"-Properties DisplayName,EmailAddress,Department,Title,"
            f"Enabled,DistinguishedName,SamAccountName,Description "
            f"| Select-Object Name,SamAccountName,DisplayName,"
            f"EmailAddress,Department,Title,Enabled,DistinguishedName,Description"
        )

    def get_user(self, sam: str):
        return self._execute(
            f"Get-ADUser -Identity '{sam}' -Properties * "
            f"| Select-Object Name,SamAccountName,DisplayName,GivenName,Surname,"
            f"EmailAddress,Department,Title,Enabled,DistinguishedName,Description,"
            f"OfficePhone,MobilePhone,PasswordNeverExpires,PasswordExpired,LockedOut"
        )

    def create_user(self, data: dict):
        sam        = data["sam_account_name"]
        first      = data.get("first_name", "")
        last       = data.get("last_name", "")
        display    = data.get("display_name") or f"{first} {last}".strip()
        email      = data.get("email", "")
        dept       = data.get("department", "")
        title      = data.get("title", "")
        desc       = data.get("description", "")
        password   = data["password"]
        ou_path    = data["ou_path"]
        enabled    = "$true" if data.get("enabled", True) else "$false"
        domain     = os.getenv("AD_DOMAIN", "lab.local")

        ps = (
            f"New-ADUser "
            f"-Name '{display}' "
            f"-GivenName '{first}' "
            f"-Surname '{last}' "
            f"-SamAccountName '{sam}' "
            f"-UserPrincipalName '{sam}@{domain}' "
            + (f"-EmailAddress '{email}' " if email else "")
            + (f"-Department '{dept}' " if dept else "")
            + (f"-Title '{title}' " if title else "")
            + (f"-Description '{desc}' " if desc else "")
            + f"-Path '{ou_path}' "
            f"-AccountPassword (ConvertTo-SecureString '{password}' -AsPlainText -Force) "
            f"-Enabled {enabled} "
            f"-ChangePasswordAtLogon $false"
        )
        self._execute_no_json(ps)
        return {"ok": True}

    def update_user(self, sam: str, data: dict):
        parts = []
        field_map = {
            "first_name":   "GivenName",
            "last_name":    "Surname",
            "display_name": "DisplayName",
            "email":        "EmailAddress",
            "department":   "Department",
            "title":        "Title",
            "description":  "Description",
        }
        for key, ps_field in field_map.items():
            if key in data and data[key] is not None:
                parts.append(f"-{ps_field} '{data[key]}'")

        if parts:
            self._execute_no_json(
                f"Set-ADUser -Identity '{sam}' {' '.join(parts)}"
            )

        if "enabled" in data:
            action = "Enable" if data["enabled"] else "Disable"
            self._execute_no_json(f"{action}-ADAccount -Identity '{sam}'")

        if data.get("password"):
            self._execute_no_json(
                f"Set-ADAccountPassword -Identity '{sam}' "
                f"-NewPassword (ConvertTo-SecureString '{data['password']}' "
                f"-AsPlainText -Force) -Reset"
            )
        return {"ok": True}

    def delete_user(self, sam: str):
        self._execute_no_json(
            f"Remove-ADUser -Identity '{sam}' -Confirm:$false"
        )
        return {"ok": True}

    def move_user(self, sam: str, target_ou: str):
        self._execute_no_json(
            f"Move-ADObject "
            f"-Identity (Get-ADUser '{sam}').DistinguishedName "
            f"-TargetPath '{target_ou}'"
        )
        return {"ok": True}

    def reset_password(self, sam: str, new_password: str):
        self._execute_no_json(
            f"Set-ADAccountPassword -Identity '{sam}' "
            f"-NewPassword (ConvertTo-SecureString '{new_password}' "
            f"-AsPlainText -Force) -Reset"
        )
        return {"ok": True}

    def unlock_user(self, sam: str):
        self._execute_no_json(f"Unlock-ADAccount -Identity '{sam}'")
        return {"ok": True}

    def toggle_user(self, sam: str, enable: bool):
        action = "Enable" if enable else "Disable"
        self._execute_no_json(f"{action}-ADAccount -Identity '{sam}'")
        return {"ok": True}

    # ------------------------------------------------------------------
    # GRUPOS
    # ------------------------------------------------------------------
    def get_groups(self, ou_dn: str = ""):
        base = f"-SearchBase '{ou_dn}'" if ou_dn else ""
        return self._execute(
            f"Get-ADGroup -Filter * {base} -Properties Description,Members "
            f"| Select-Object Name,SamAccountName,GroupCategory,GroupScope,"
            f"Description,DistinguishedName,"
            f"@{{N='MemberCount';E={{$_.Members.Count}}}}"
        )

    def get_group_members(self, sam: str):
        return self._execute(
            f"Get-ADGroupMember -Identity '{sam}' "
            f"| Select-Object Name,SamAccountName,objectClass"
        )

    def create_group(self, data: dict):
        name     = data["name"]
        sam      = data.get("sam_account_name") or name
        scope    = data.get("scope", "Global")
        category = data.get("category", "Security")
        desc     = data.get("description", "")
        ou_path  = data["ou_path"]

        self._execute_no_json(
            f"New-ADGroup "
            f"-Name '{name}' "
            f"-SamAccountName '{sam}' "
            f"-GroupScope {scope} "
            f"-GroupCategory {category} "
            f"-Description '{desc}' "
            f"-Path '{ou_path}'"
        )
        return {"ok": True}

    def delete_group(self, sam: str):
        self._execute_no_json(
            f"Remove-ADGroup -Identity '{sam}' -Confirm:$false"
        )
        return {"ok": True}

    def add_user_to_group(self, user_sam: str, group_sam: str):
        self._execute_no_json(
            f"Add-ADGroupMember -Identity '{group_sam}' -Members '{user_sam}'"
        )
        return {"ok": True}

    def remove_user_from_group(self, user_sam: str, group_sam: str):
        self._execute_no_json(
            f"Remove-ADGroupMember -Identity '{group_sam}' "
            f"-Members '{user_sam}' -Confirm:$false"
        )
        return {"ok": True}

    # ------------------------------------------------------------------
    # EQUIPOS / COMPUTADORAS
    # ------------------------------------------------------------------
    def get_computers(self, ou_dn: str = ""):
        base = f"-SearchBase '{ou_dn}'" if ou_dn else ""
        return self._execute(
            f"Get-ADComputer -Filter * {base} "
            f"-Properties Description,OperatingSystem,LastLogonDate,Enabled "
            f"| Select-Object Name,SamAccountName,Description,"
            f"OperatingSystem,LastLogonDate,Enabled,DistinguishedName"
        )

    def delete_computer(self, sam: str):
        self._execute_no_json(
            f"Remove-ADComputer -Identity '{sam}' -Confirm:$false"
        )
        return {"ok": True}

    def move_computer(self, sam: str, target_ou: str):
        self._execute_no_json(
            f"Move-ADObject "
            f"-Identity (Get-ADComputer '{sam}').DistinguishedName "
            f"-TargetPath '{target_ou}'"
        )
        return {"ok": True}

    # ------------------------------------------------------------------
    # BÚSQUEDA GLOBAL
    # ------------------------------------------------------------------
    def search(self, query: str):
        ps = f"""
$q = '*{query}*'
$results = @()
$results += Get-ADUser -Filter {{Name -like $q -or SamAccountName -like $q}} `
    -Properties DisplayName,Enabled `
    | Select-Object @{{N='Type';E={{'Usuario'}}}},Name,SamAccountName,DisplayName,Enabled,DistinguishedName
$results += Get-ADGroup -Filter {{Name -like $q}} `
    | Select-Object @{{N='Type';E={{'Grupo'}}}},Name,SamAccountName,@{{N='DisplayName';E={{$_.Name}}}},@{{N='Enabled';E={{$true}}}},DistinguishedName
$results += Get-ADComputer -Filter {{Name -like $q}} -Properties Enabled `
    | Select-Object @{{N='Type';E={{'Equipo'}}}},Name,SamAccountName,@{{N='DisplayName';E={{$_.Name}}}},Enabled,DistinguishedName
$results | ConvertTo-Json -Depth 3 -Compress
"""
        raw = self._run(f"Import-Module ActiveDirectory; {ps}")
        if not raw:
            return []
        try:
            text = raw.decode("cp1252")
        except Exception:
            text = raw.decode("utf-8", errors="replace")
        text = text.strip()
        if not text:
            return []
        data = json.loads(text)
        return data if isinstance(data, list) else [data]
