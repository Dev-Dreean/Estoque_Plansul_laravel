#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para converter arquivos de importação TXT para SQL INSERT (complementar)
Compatível com Kinghost
Data: 26 de novembro de 2025
"""

import os
import re
from datetime import datetime

def parse_txt_file(filepath):
    """
    Parse arquivo TXT com formato tabulado
    Retorna lista de dicionários com os dados
    """
    data = []
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()
    
    if len(lines) < 2:
        return []
    
    # Primera línea = headers
    headers = lines[0].split('\t')
    headers = [h.strip() for h in headers if h.strip()]
    
    # Procesar datos (saltando línea 2 que es separador)
    for i, line in enumerate(lines[2:], start=2):
        if line.strip() and not line.startswith('='):
            values = line.split('\t')
            record = {}
            for j, header in enumerate(headers):
                if j < len(values):
                    val = values[j].strip()
                    record[header] = val if val and val != '<null>' else None
            if any(record.values()):  # Solo si hay al menos un valor no nulo
                data.append(record)
    
    return data, headers

def escape_sql_string(value):
    """Escape string para SQL"""
    if value is None:
        return 'NULL'
    value = str(value).replace("'", "''")
    return f"'{value}'"

def generate_insert_statements(data, headers, table_name):
    """Generar statements INSERT IGNORE"""
    if not data:
        return []
    
    statements = []
    for record in data:
        cols = []
        vals = []
        for header in headers:
            if header in record and record[header] is not None:
                cols.append(f"`{header}`")
                vals.append(escape_sql_string(record[header]))
        
        if cols:
            stmt = f"INSERT IGNORE INTO {table_name} ({', '.join(cols)}) VALUES ({', '.join(vals)});"
            statements.append(stmt)
    
    return statements

def main():
    imports_dir = os.path.dirname(os.path.abspath(__file__))
    
    files_to_process = {
        'LOCALPROJETO.TXT': 'locais_projeto',
        'PROJETOTABFANTASIA.TXT': 'tabfant',
        'PATRIMONIO.TXT': 'patr',
        'MOVPATRHISTORICO.TXT': 'historico_movimentacao'
    }
    
    all_statements = []
    
    # Agregar encabezado
    all_statements.append("-- ============================================================================")
    all_statements.append("-- IMPORTAÇÃO COMPLEMENTAR KINGHOST - SQL INSERT")
    all_statements.append(f"-- Gerado em: {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}")
    all_statements.append("-- ============================================================================")
    all_statements.append("")
    all_statements.append("SET FOREIGN_KEY_CHECKS=0;")
    all_statements.append("")
    
    for filename, table_name in files_to_process.items():
        filepath = os.path.join(imports_dir, filename)
        if os.path.exists(filepath):
            print(f"Processando {filename}...")
            data, headers = parse_txt_file(filepath)
            
            if data:
                all_statements.append(f"-- ============================================================================")
                all_statements.append(f"-- TABELA: {table_name}")
                all_statements.append(f"-- Total de registros: {len(data)}")
                all_statements.append(f"-- ============================================================================")
                all_statements.append("")
                
                inserts = generate_insert_statements(data, headers, table_name)
                all_statements.extend(inserts)
                all_statements.append("")
                
                print(f"  ✓ {len(data)} registros processados")
            else:
                print(f"  ⚠ Nenhum dado encontrado em {filename}")
    
    all_statements.append("SET FOREIGN_KEY_CHECKS=1;")
    all_statements.append("")
    all_statements.append(f"-- Fim da importação em {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}")
    
    # Salvar arquivo
    output_file = os.path.join(imports_dir, 'import_kinghost_inserts.sql')
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write('\n'.join(all_statements))
    
    print(f"\n✓ Arquivo gerado: {output_file}")
    print(f"  Total de linhas: {len(all_statements)}")

if __name__ == '__main__':
    main()
