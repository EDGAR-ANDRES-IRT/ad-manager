import winrm

s = winrm.Session('http://172.16.10.2:5985/wsman', auth=('Administrador', 'Eaha823'), transport='ntlm')

r = s.run_ps('Get-Date')
print(r.std_out.decode())
