import sys
import logging
from os.path import dirname, join

# Adjust the sys.path to include the directory of your Flask app
sys.path.insert(0, dirname(__file__))

from app import app as application

# Optional: Log any errors for debugging
logging.basicConfig(stream=sys.stderr)
