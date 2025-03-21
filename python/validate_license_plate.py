import json
import re

def load_constraints(file_name):
    """Carrega as restrições de um arquivo JSON."""
    with open(file_name, 'r') as file:
        return json.load(file)

def validate_license_plate(license_plate, constraints):
    """Valida a placa de acordo com as restrições"""
    # Verificar comprimento
    if len(license_plate) != constraints['length']:
        return False

    # Verificar número de letras
    letters = re.sub(r'[^a-zA-Z]', '', license_plate)
    if len(letters) != constraints['letters']:
        return False

    # Verificar número de números
    numbers = re.sub(r'[^0-9]', '', license_plate)
    if len(numbers) != constraints['numbers']:
        return False

    return True

# Exemplo de uso
constraints = load_constraints("brasil.cnf")
license_plate = input("Digite a placa do veículo: ")

if validate_license_plate(license_plate, constraints):
    print(f"A placa {license_plate} é válida.")
else:
    print(f"A placa {license_plate} é inválida.")
