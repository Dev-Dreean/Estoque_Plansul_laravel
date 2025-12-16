#!/usr/bin/env python3
"""
ONE-OFF REVERTER: Restaurar show.blade.php original (remover placeholders de teste)
"""

import shutil

SHOW_FILE = 'resources/views/patrimonios/show.blade.php'
BACKUP_FILE = 'resources/views/patrimonios/show_backup_placeholders.blade.php'

shutil.copy(BACKUP_FILE, SHOW_FILE)
print(f"✅ Revertido: {SHOW_FILE} restaurado do backup")
print(f"Para deletar backup: rm {BACKUP_FILE}")
