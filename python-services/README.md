# Certificate Parsing Service

This service provides functionality to parse X.509 certificates from various formats including PEM, DER, and certificate files. It extracts key information such as subject, issuer, validity dates, serial number, and public key details.

## Features

- Parse certificates in PEM and DER formats
- Extract subject and issuer information
- Get validity dates and duration
- Retrieve serial number and signature algorithm
- Parse public key information
- Extract Subject Alternative Names (SANs)

## Installation

```bash
cd python-services
pip install -r requirements.txt
```

## Usage

### Command Line Interface

```bash
# Parse certificate and output as JSON
python certificate_parser.py --input-file cert.pem

# Parse certificate and output as text
python certificate_parser.py --input-file cert.pem --output-format text
```

### As a Module

```python
from certificate_parser import CertificateParser

# Create parser instance
parser = CertificateParser()

# Load certificate from file
parser.load_certificate_from_file('certificate.pem')

# Extract information
info = parser.get_certificate_info()
print(info)
```

## Output Format

The service outputs certificate information in the following structure:

```json
{
  "subject": {
    "country": "US",
    "state": "California",
    "locality": "San Francisco",
    "organization": "Example Inc.",
    "organizational_unit": "IT Department",
    "common_name": "example.com",
    "email": "admin@example.com"
  },
  "issuer": {
    "country": "US",
    "state": "California",
    "locality": "San Francisco",
    "organization": "Example CA",
    "organizational_unit": "Certification Authority",
    "common_name": "Example CA",
    "email": "ca@example.com"
  },
  "validity": {
    "not_before": "2023-01-01 00:00:00 UTC",
    "not_after": "2024-01-01 00:00:00 UTC",
    "days_valid": 365
  },
  "serial_number": "0012AB34CD",
  "signature_algorithm": "sha256WithRSAEncryption",
  "public_key": {
    "type": 6,
    "bits": 2048
  },
  "version": 2,
  "subject_alternative_names": [
    {
      "type": "DNS",
      "value": "example.com"
    },
    {
      "type": "DNS",
      "value": "www.example.com"
    }
  ]
}
```

## Dependencies

- pyOpenSSL >= 22.0.0
- cryptography >= 38.0.0
