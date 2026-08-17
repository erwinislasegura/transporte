
from pathlib import Path
import sqlite3
base=Path(__file__).resolve().parent
db=base/"bgv_enterprise.db"
if db.exists(): db.unlink()
conn=sqlite3.connect(db)
conn.executescript((base/"schema.sql").read_text(encoding="utf-8"))
conn.executescript((base/"seed.sql").read_text(encoding="utf-8"))
conn.commit(); conn.close()
print(f"Base creada: {db}")
