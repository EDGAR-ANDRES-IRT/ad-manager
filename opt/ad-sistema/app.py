from flask import Flask, jsonify, request
from flask_cors import CORS
from ad_helper import ADManager

app = Flask(__name__)
CORS(app)

# Instancia global — la sesión WinRM se reutiliza
ad = ADManager()

# ──────────────────────────────────────────────
# HELPERS
# ──────────────────────────────────────────────
def ok(data):
    return jsonify({"status": "success", "data": data})

def err(msg, code=500):
    return jsonify({"status": "error", "message": str(msg)}), code

# ──────────────────────────────────────────────
# DOMINIO
# ──────────────────────────────────────────────
@app.route("/api/dominio")
def dominio():
    try:
        return ok(ad.get_domain_info())
    except Exception as e:
        return err(e)

@app.route("/api/dominio/stats")
def dominio_stats():
    try:
        return ok(ad.get_domain_stats())
    except Exception as e:
        return err(e)

# ──────────────────────────────────────────────
# UNIDADES ORGANIZATIVAS
# ──────────────────────────────────────────────
@app.route("/api/ous")
def get_ous():
    try:
        return ok(ad.get_ous())
    except Exception as e:
        return err(e)

@app.route("/api/ous", methods=["POST"])
def create_ou():
    data = request.get_json()
    if not data or not data.get("name") or not data.get("path"):
        return err("name y path son requeridos", 400)
    try:
        ad.create_ou(data["name"], data["path"], data.get("description", ""))
        return ok({"message": f"OU '{data['name']}' creada correctamente"})
    except Exception as e:
        return err(e)

@app.route("/api/ous", methods=["DELETE"])
def delete_ou():
    data = request.get_json()
    if not data or not data.get("dn"):
        return err("dn es requerido", 400)
    try:
        ad.delete_ou(data["dn"])
        return ok({"message": "OU eliminada correctamente"})
    except Exception as e:
        return err(e)

# ──────────────────────────────────────────────
# USUARIOS
# ──────────────────────────────────────────────
@app.route("/api/usuarios")
def get_users():
    ou = request.args.get("ou", "")
    try:
        return ok(ad.get_users(ou))
    except Exception as e:
        return err(e)

@app.route("/api/usuarios/<sam>")
def get_user(sam):
    try:
        return ok(ad.get_user(sam))
    except Exception as e:
        return err(e)

@app.route("/api/usuarios", methods=["POST"])
def create_user():
    data = request.get_json()
    required = ["sam_account_name", "password", "ou_path", "first_name"]
    missing = [f for f in required if not data.get(f)]
    if missing:
        return err(f"Campos requeridos: {', '.join(missing)}", 400)
    try:
        ad.create_user(data)
        return ok({"message": f"Usuario '{data['sam_account_name']}' creado correctamente"})
    except Exception as e:
        return err(e)

@app.route("/api/usuarios/<sam>", methods=["PUT"])
def update_user(sam):
    data = request.get_json()
    try:
        ad.update_user(sam, data)
        return ok({"message": f"Usuario '{sam}' actualizado"})
    except Exception as e:
        return err(e)

@app.route("/api/usuarios/<sam>", methods=["DELETE"])
def delete_user(sam):
    try:
        ad.delete_user(sam)
        return ok({"message": f"Usuario '{sam}' eliminado"})
    except Exception as e:
        return err(e)

@app.route("/api/usuarios/<sam>/mover", methods=["POST"])
def move_user(sam):
    data = request.get_json()
    if not data or not data.get("target_ou"):
        return err("target_ou es requerido", 400)
    try:
        ad.move_user(sam, data["target_ou"])
        return ok({"message": f"Usuario '{sam}' movido"})
    except Exception as e:
        return err(e)

@app.route("/api/usuarios/<sam>/reset-password", methods=["POST"])
def reset_password(sam):
    data = request.get_json()
    if not data or not data.get("password"):
        return err("password es requerido", 400)
    try:
        ad.reset_password(sam, data["password"])
        return ok({"message": f"Contraseña de '{sam}' restablecida"})
    except Exception as e:
        return err(e)

@app.route("/api/usuarios/<sam>/unlock", methods=["POST"])
def unlock_user(sam):
    try:
        ad.unlock_user(sam)
        return ok({"message": f"Cuenta '{sam}' desbloqueada"})
    except Exception as e:
        return err(e)

@app.route("/api/usuarios/<sam>/toggle", methods=["POST"])
def toggle_user(sam):
    data = request.get_json()
    if data is None or "enabled" not in data:
        return err("enabled (true/false) es requerido", 400)
    try:
        ad.toggle_user(sam, bool(data["enabled"]))
        estado = "habilitado" if data["enabled"] else "deshabilitado"
        return ok({"message": f"Usuario '{sam}' {estado}"})
    except Exception as e:
        return err(e)

# ──────────────────────────────────────────────
# GRUPOS
# ──────────────────────────────────────────────
@app.route("/api/grupos")
def get_groups():
    ou = request.args.get("ou", "")
    try:
        return ok(ad.get_groups(ou))
    except Exception as e:
        return err(e)

@app.route("/api/grupos/<sam>/miembros")
def get_group_members(sam):
    try:
        return ok(ad.get_group_members(sam))
    except Exception as e:
        return err(e)

@app.route("/api/grupos", methods=["POST"])
def create_group():
    data = request.get_json()
    if not data or not data.get("name") or not data.get("ou_path"):
        return err("name y ou_path son requeridos", 400)
    try:
        ad.create_group(data)
        return ok({"message": f"Grupo '{data['name']}' creado correctamente"})
    except Exception as e:
        return err(e)

@app.route("/api/grupos/<sam>", methods=["DELETE"])
def delete_group(sam):
    try:
        ad.delete_group(sam)
        return ok({"message": f"Grupo '{sam}' eliminado"})
    except Exception as e:
        return err(e)

@app.route("/api/grupos/<sam>/miembros", methods=["POST"])
def add_member(sam):
    data = request.get_json()
    if not data or not data.get("user_sam"):
        return err("user_sam es requerido", 400)
    try:
        ad.add_user_to_group(data["user_sam"], sam)
        return ok({"message": f"'{data['user_sam']}' agregado al grupo '{sam}'"})
    except Exception as e:
        return err(e)

@app.route("/api/grupos/<sam>/miembros/<user_sam>", methods=["DELETE"])
def remove_member(sam, user_sam):
    try:
        ad.remove_user_from_group(user_sam, sam)
        return ok({"message": f"'{user_sam}' removido del grupo '{sam}'"})
    except Exception as e:
        return err(e)

# ──────────────────────────────────────────────
# EQUIPOS / COMPUTADORAS
# ──────────────────────────────────────────────
@app.route("/api/equipos")
def get_computers():
    ou = request.args.get("ou", "")
    try:
        return ok(ad.get_computers(ou))
    except Exception as e:
        return err(e)

@app.route("/api/equipos/<sam>", methods=["DELETE"])
def delete_computer(sam):
    try:
        ad.delete_computer(sam)
        return ok({"message": f"Equipo '{sam}' eliminado del AD"})
    except Exception as e:
        return err(e)

@app.route("/api/equipos/<sam>/mover", methods=["POST"])
def move_computer(sam):
    data = request.get_json()
    if not data or not data.get("target_ou"):
        return err("target_ou es requerido", 400)
    try:
        ad.move_computer(sam, data["target_ou"])
        return ok({"message": f"Equipo '{sam}' movido"})
    except Exception as e:
        return err(e)

# ──────────────────────────────────────────────
# BÚSQUEDA
# ──────────────────────────────────────────────
@app.route("/api/buscar")
def search():
    q = request.args.get("q", "").strip()
    if not q:
        return err("Parámetro q es requerido", 400)
    try:
        return ok(ad.search(q))
    except Exception as e:
        return err(e)

# ──────────────────────────────────────────────
# HEALTH CHECK
# ──────────────────────────────────────────────
@app.route("/api/ping")
def ping():
    return ok({"message": "API operativa"})

# ──────────────────────────────────────────────
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False)
