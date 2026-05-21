import os
from dotenv import load_dotenv

# Nạp file .env từ thư mục hiện tại hoặc thư mục gốc dự án
load_dotenv()

# Lấy các biến môi trường
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY", "")
GEMINI_MODEL = os.getenv("GEMINI_MODEL", "gemini-2.5-flash")
PORT = int(os.getenv("AI_SERVICE_PORT", 8000))
HOST = os.getenv("AI_SERVICE_HOST", "127.0.0.1")

if not GEMINI_API_KEY:
    print("[WARNING] GEMINI_API_KEY is not set. LLM features will fail unless configured.")
