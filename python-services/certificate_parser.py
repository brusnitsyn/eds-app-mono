#!/usr/bin/env python3
"""
Certificate Parsing Service
===========================

This service provides functionality to parse X.509 certificates from various formats
including PEM, DER, and certificate files. It extracts key information such as
subject, issuer, validity dates, serial number, and public key details.

Usage:
    python certificate_parser.py --input-file cert.pem
"""

import argparse
import base64
import os
import re
import urllib.request
from datetime import datetime
from typing import Dict, Any, Optional
import OpenSSL.crypto
from OpenSSL.crypto import X509Name
from cryptography.x509 import load_der_x509_crl


class CertificateParser:
    """A class to parse X.509 certificates."""
    
    def __init__(self):
        self.cert = None
        self.cert_der: Optional[bytes] = None
    
    def load_certificate_from_file(self, file_path: str) -> bool:
        """
        Load certificate from file.
        
        Args:
            file_path: Path to certificate file
            
        Returns:
            True if successful, False otherwise
        """
        try:
            with open(file_path, 'rb') as f:
                cert_data = f.read()
            
            # Try to detect certificate format
            if cert_data.startswith(b'-----BEGIN CERTIFICATE-----'):
                # PEM format
                self.cert = OpenSSL.crypto.load_certificate(
                    OpenSSL.crypto.FILETYPE_PEM, cert_data
                )
            else:
                # DER format
                self.cert = OpenSSL.crypto.load_certificate(
                    OpenSSL.crypto.FILETYPE_ASN1, cert_data
                )

            self.cert_der = OpenSSL.crypto.dump_certificate(
                OpenSSL.crypto.FILETYPE_ASN1, self.cert
            )
            return True
        except Exception as e:
            print(f"Error loading certificate: {e}")
            return False
    
    def load_certificate_from_string(self, cert_string: str) -> bool:
        """
        Load certificate from string.
        
        Args:
            cert_string: Certificate data as string
            
        Returns:
            True if successful, False otherwise
        """
        try:
            if cert_string.startswith('-----BEGIN CERTIFICATE-----'):
                # PEM format
                self.cert = OpenSSL.crypto.load_certificate(
                    OpenSSL.crypto.FILETYPE_PEM, cert_string.encode()
                )
            else:
                # DER format (base64 encoded)
                der_data = base64.b64decode(cert_string)
                self.cert = OpenSSL.crypto.load_certificate(
                    OpenSSL.crypto.FILETYPE_ASN1, der_data
                )

            self.cert_der = OpenSSL.crypto.dump_certificate(
                OpenSSL.crypto.FILETYPE_ASN1, self.cert
            )
            return True
        except Exception as e:
            print(f"Error loading certificate from string: {e}")
            return False
    
    def get_certificate_info(self) -> Dict[str, Any]:
        """
        Extract comprehensive information from certificate.
        
        Returns:
            Dictionary containing certificate information
        """
        if not self.cert:
            raise ValueError("No certificate loaded")
        
        info = {}
        
        # Subject information
        subject = self.cert.get_subject()
        info['subject'] = {
            'country': self._safe_name_attr(subject, 'C'),
            'state': self._safe_name_attr(subject, 'ST'),
            'locality': self._safe_name_attr(subject, 'L'),
            'organization': self._safe_name_attr(subject, 'O'),
            'organizational_unit': self._safe_name_attr(subject, 'OU'),
            'common_name': self._safe_name_attr(subject, 'CN'),
            'email': self._safe_name_attr(subject, 'Email'),
        }
        
        # Issuer information
        issuer = self.cert.get_issuer()
        info['issuer'] = {
            'country': self._safe_name_attr(issuer, 'C'),
            'state': self._safe_name_attr(issuer, 'ST'),
            'locality': self._safe_name_attr(issuer, 'L'),
            'organization': self._safe_name_attr(issuer, 'O'),
            'organizational_unit': self._safe_name_attr(issuer, 'OU'),
            'common_name': self._safe_name_attr(issuer, 'CN'),
            'email': self._safe_name_attr(issuer, 'Email'),
        }
        
        # Validity dates
        not_before = self.cert.get_notBefore()
        not_after = self.cert.get_notAfter()
        
        info['validity'] = {
            'not_before': self._parse_date(not_before),
            'not_after': self._parse_date(not_after),
            'days_valid': self._calculate_days_valid(not_before, not_after)
        }
        
        # Serial number as uppercase hex string with preserved leading zero bytes.
        info['serial_number'] = self._get_serial_number_hex()
        
        # Signature algorithm
        info['signature_algorithm'] = self.cert.get_signature_algorithm().decode('utf-8')
        
        # Public key information
        pub_key = self.cert.get_pubkey()
        info['public_key'] = {
            'type': pub_key.type(),
            'bits': pub_key.bits(),
        }
        
        # Certificate version
        info['version'] = self.cert.get_version()
        
        # Subject Alternative Names (if present)
        info['subject_alternative_names'] = self._get_subject_alternative_names()

        # Best-effort revocation check against the certificate's own CRL
        # distribution point (never raises — always returns a status dict).
        info['revocation'] = self.check_revocation()

        return info

    def _get_crl_urls(self) -> list:
        """Extract CRL distribution point URLs from the certificate, if any."""
        urls = []
        try:
            for i in range(self.cert.get_extension_count()):
                ext = self.cert.get_extension(i)
                if ext.get_short_name() == b'crlDistributionPoints':
                    urls.extend(re.findall(r'URI:(\S+)', str(ext)))
        except Exception as e:
            print(f"Warning: Could not extract CRL distribution points: {e}")
        return urls

    def check_revocation(self) -> Dict[str, Any]:
        """
        Best-effort CRL revocation check.

        Fails open: if the certificate has no CRL distribution point, or the
        CRL can't be fetched/parsed (e.g. no network access to the CA), this
        reports checked=False with an explanation rather than pretending the
        certificate is valid or revoked.
        """
        urls = self._get_crl_urls()
        if not urls:
            return {
                'checked': False,
                'revoked': None,
                'source': None,
                'error': 'У сертификата не указана точка распространения CRL',
            }

        try:
            serial_int = int(self._get_serial_number_hex(), 16)
        except (ValueError, TypeError):
            return {
                'checked': False,
                'revoked': None,
                'source': None,
                'error': 'Не удалось определить серийный номер для проверки CRL',
            }

        last_error = None
        for url in urls:
            try:
                with urllib.request.urlopen(url, timeout=5) as response:
                    crl_bytes = response.read()
                crl = load_der_x509_crl(crl_bytes)
                revoked = crl.get_revoked_certificate_by_serial_number(serial_int) is not None
                return {'checked': True, 'revoked': revoked, 'source': url, 'error': None}
            except Exception as e:
                last_error = f"{url}: {e}"
                continue

        return {
            'checked': False,
            'revoked': None,
            'source': urls[0],
            'error': last_error or 'Сервис CRL недоступен',
        }

    def _get_serial_number_hex(self) -> str:
        """
        Extract serial number from the DER-encoded certificate.

        Returns:
            Uppercase hex string with leading zero bytes preserved
        """
        if not self.cert_der:
            raise ValueError("No certificate data loaded")

        cert_bytes = self.cert_der
        cert_value_offset = self._read_expected_tag(cert_bytes, 0, 0x30)
        current_offset = self._read_expected_tag(cert_bytes, cert_value_offset, 0x30)

        # Skip optional version field: [0] EXPLICIT Version
        if cert_bytes[current_offset] == 0xA0:
            _, _, current_offset = self._read_tlv(cert_bytes, current_offset)

        serial_bytes, _, _ = self._read_tlv(cert_bytes, current_offset)
        if not serial_bytes:
            return ""

        return serial_bytes.hex().upper()

    def _safe_name_attr(self, name: X509Name, attr: str) -> str:
        """Return a subject/issuer attribute or an empty string if it is missing."""
        return getattr(name, attr, "")

    def _read_expected_tag(self, data: bytes, offset: int, expected_tag: int) -> int:
        """Read a TLV and return the value start offset for the expected tag."""
        tag = data[offset]
        if tag != expected_tag:
            raise ValueError(f"Unexpected ASN.1 tag: expected {expected_tag:#x}, got {tag:#x}")

        _, value_start, _ = self._read_tlv(data, offset)
        return value_start

    def _read_tlv(self, data: bytes, offset: int) -> tuple[bytes, int, int]:
        """
        Read a single ASN.1 TLV item.

        Returns:
            (value_bytes, value_start_offset, next_offset)
        """
        if offset >= len(data):
            raise ValueError("ASN.1 offset out of range")

        length, length_bytes = self._read_length(data, offset + 1)
        value_start = offset + 1 + length_bytes
        value_end = value_start + length

        if value_end > len(data):
            raise ValueError("ASN.1 length exceeds available data")

        return data[value_start:value_end], value_start, value_end

    def _read_length(self, data: bytes, offset: int) -> tuple[int, int]:
        """Read ASN.1 DER length and return (length, consumed_bytes)."""
        first_byte = data[offset]
        if first_byte < 0x80:
            return first_byte, 1

        num_bytes = first_byte & 0x7F
        if num_bytes == 0:
            raise ValueError("Indefinite lengths are not allowed in DER")

        length_start = offset + 1
        length_end = length_start + num_bytes
        if length_end > len(data):
            raise ValueError("ASN.1 length header exceeds available data")

        return int.from_bytes(data[length_start:length_end], byteorder='big'), 1 + num_bytes
    
    def _parse_date(self, date_bytes: bytes) -> str:
        """
        Parse ASN.1 date format to readable string.
        
        Args:
            date_bytes: Date in ASN.1 format
            
        Returns:
            Formatted date string
        """
        if not date_bytes:
            return ""
        
        date_str = date_bytes.decode('utf-8')
        # Format: YYYYMMDDHHMMSSZ
        try:
            dt = datetime.strptime(date_str, '%Y%m%d%H%M%SZ')
            return dt.strftime('%Y-%m-%d %H:%M:%S UTC')
        except ValueError:
            return date_str
    
    def _calculate_days_valid(self, not_before: bytes, not_after: bytes) -> int:
        """
        Calculate number of days certificate is valid.
        
        Args:
            not_before: Not before date
            not_after: Not after date
            
        Returns:
            Number of days valid
        """
        try:
            before = datetime.strptime(not_before.decode('utf-8'), '%Y%m%d%H%M%SZ')
            after = datetime.strptime(not_after.decode('utf-8'), '%Y%m%d%H%M%SZ')
            return (after - before).days
        except Exception:
            return 0
    
    def _get_subject_alternative_names(self) -> list:
        """
        Extract Subject Alternative Names from certificate.
        
        Returns:
            List of SANs
        """
        sans = []
        try:
            # Get extensions
            for i in range(self.cert.get_extension_count()):
                ext = self.cert.get_extension(i)
                if ext.get_short_name() == b'subjectAltName':
                    san_str = str(ext)
                    # Parse SANs
                    for san in san_str.split(','):
                        san = san.strip()
                        if ':' in san:
                            san_type, san_value = san.split(':', 1)
                            sans.append({
                                'type': san_type.strip(),
                                'value': san_value.strip()
                            })
        except Exception as e:
            print(f"Warning: Could not extract SANs: {e}")
        
        return sans


def main():
    """Main function to parse command line arguments and process certificate."""
    parser = argparse.ArgumentParser(description='Parse X.509 certificates')
    parser.add_argument('--input-file', '-f', required=True,
                       help='Path to certificate file (PEM or DER format)')
    parser.add_argument('--output-format', '-o', choices=['json', 'text'],
                       default='json',
                       help='Output format (default: json)')
    
    args = parser.parse_args()
    
    # Check if file exists
    if not os.path.exists(args.input_file):
        print(f"Error: File '{args.input_file}' does not exist")
        return 1
    
    # Create parser instance
    parser_service = CertificateParser()
    
    # Load certificate
    if not parser_service.load_certificate_from_file(args.input_file):
        print("Error: Failed to load certificate")
        return 1
    
    # Extract information
    try:
        cert_info = parser_service.get_certificate_info()
    except Exception as e:
        print(f"Error extracting certificate information: {e}")
        return 1
    
    # Output results
    if args.output_format == 'json':
        import json
        print(json.dumps(cert_info, indent=2))
    else:
        # Text format
        print("Certificate Information:")
        print("=" * 50)
        print(f"Subject: {cert_info['subject']['common_name']}")
        print(f"Issuer: {cert_info['issuer']['common_name']}")
        print(f"Serial Number: {cert_info['serial_number']}")
        print(f"Version: {cert_info['version']}")
        print(f"Signature Algorithm: {cert_info['signature_algorithm']}")
        print(f"Valid From: {cert_info['validity']['not_before']}")
        print(f"Valid Until: {cert_info['validity']['not_after']}")
        print(f"Days Valid: {cert_info['validity']['days_valid']}")
        print(f"Public Key Type: {cert_info['public_key']['type']}")
        print(f"Public Key Bits: {cert_info['public_key']['bits']}")
        
        if cert_info['subject_alternative_names']:
            print("Subject Alternative Names:")
            for san in cert_info['subject_alternative_names']:
                print(f"  {san['type']}: {san['value']}")
    
    return 0


if __name__ == "__main__":
    exit(main())
