import unittest
import json
from io import StringIO
from unittest.mock import patch
from validate_license_plate import load_constraints, validate_license_plate


class TestLicensePlateValidation(unittest.TestCase):
    
    @patch('builtins.open', return_value=StringIO('{"length": 6, "letters": 3, "numbers": 3}'))
    def test_load_constraints(self, mock_open):
        """Testa a função que carrega as restrições do arquivo JSON"""
        constraints = load_constraints("brasil.cnf")
        self.assertEqual(constraints['length'], 6)
        self.assertEqual(constraints['letters'], 3)
        self.assertEqual(constraints['numbers'], 3)

    def test_valid_license_plate(self):
        """Testa uma placa válida"""
        constraints = {
            "length": 6,
            "letters": 3,
            "numbers": 3
        }
        valid_plate = "ABC123"
        result = validate_license_plate(valid_plate, constraints)
        self.assertTrue(result)

    def test_invalid_license_plate_length(self):
        """Testa uma placa com comprimento incorreto"""
        constraints = {
            "length": 6,
            "letters": 3,
            "numbers": 3
        }
        invalid_plate = "AB123"
        result = validate_license_plate(invalid_plate, constraints)
        self.assertFalse(result)

    def test_invalid_license_plate_letters(self):
        """Testa uma placa com número incorreto de letras"""
        constraints = {
            "length": 6,
            "letters": 3,
            "numbers": 3
        }
        invalid_plate = "AB1234"
        result = validate_license_plate(invalid_plate, constraints)
        self.assertFalse(result)

    def test_invalid_license_plate_numbers(self):
        """Testa uma placa com número incorreto de números"""
        constraints = {
            "length": 6,
            "letters": 3,
            "numbers": 3
        }
        invalid_plate = "ABC12"
        result = validate_license_plate(invalid_plate, constraints)
        self.assertFalse(result)

    def test_invalid_license_plate_non_alphanumeric(self):
        """Testa uma placa com caracteres não alfanuméricos"""
        constraints = {
            "length": 6,
            "letters": 3,
            "numbers": 3
        }
        invalid_plate = "A@B123"
        result = validate_license_plate(invalid_plate, constraints)
        self.assertFalse(result)


if __name__ == '__main__':
    unittest.main()
