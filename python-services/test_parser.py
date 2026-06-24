#!/usr/bin/env python3
"""
Test script for certificate parser service
"""

import tempfile
import os
from certificate_parser import CertificateParser

def create_test_certificate():
    """Create a simple test certificate for demonstration purposes."""
    # In a real scenario, you would generate or obtain a real certificate
    # For this demo, we'll just show how to use the parser
    
    print("Certificate Parser Test Script")
    print("=" * 40)
    print("This script demonstrates how to use the certificate parser.")
    print()
    print("To test with a real certificate:")
    print("1. Place a certificate file (PEM or DER format) in this directory")
    print("2. Run: python certificate_parser.py --input-file your_cert.pem")
    print()
    print("Example usage:")
    print(">>> parser = CertificateParser()")
    print(">>> parser.load_certificate_from_file('test_cert.pem')")
    print(">>> info = parser.get_certificate_info()")
    print(">>> print(info)")

if __name__ == "__main__":
    create_test_certificate()