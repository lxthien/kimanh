import json
import google.generativeai as genai
from config import GEMINI_API_KEY, GEMINI_MODEL

class LLMClient:
    def __init__(self):
        if GEMINI_API_KEY:
            genai.configure(api_key=GEMINI_API_KEY)
        self.model_name = GEMINI_MODEL

    def analyze_content(self, title: str, content: str) -> dict:
        """
        Gửi yêu cầu đánh giá nội dung bài viết sang Gemini API.
        Sử dụng Structured Outputs (response_mime_type="application/json") để nhận định dạng chuẩn.
        """
        if not GEMINI_API_KEY:
            return self._fallback_response("Chưa cấu hình GEMINI_API_KEY ở AI Service.")

        prompt = f"""
Bạn là một chuyên gia SEO hàng đầu chuyên đánh giá chất lượng nội dung bài viết theo các thuật toán tìm kiếm mới nhất của Google.
Nhiệm vụ của bạn là đánh giá bài viết dưới đây về kiến trúc/xây dựng và trả về một cấu trúc JSON chi tiết.

Tiêu đề: {title}
Nội dung:
{content}

Yêu cầu JSON trả về phải tuân thủ đúng cấu trúc Schema sau:
{{
  "eeat_score": 85.0, // Điểm số từ 0 đến 100
  "eeat_feedback": {{
    "expertise": "Phân tích chi tiết về mặt chuyên môn thiết kế/kỹ thuật thi công trong bài viết.",
    "authoritativeness": "Đánh giá các dẫn chứng, dự án thực tế hoặc chứng chỉ năng lực được nhắc đến trong bài viết.",
    "trustworthiness": "Đánh giá sự trung thực, minh bạch về thông tin tác giả, nguồn gốc thông tin và cam kết."
  }},
  "aeo_score": 70.0, // Điểm số từ 0 đến 100
  "aeo_feedback": {{
    "direct_answer": "Đánh giá xem bài viết có cung cấp câu trả lời rõ ràng trực diện cho câu hỏi chính ở đầu bài viết không.",
    "faq_format": "Phân tích cấu trúc FAQ (Câu hỏi thường gặp) có được tối ưu không.",
    "suggestions": [
      "Gợi ý 1 để cải thiện khả năng trả lời cho Google AI Overview",
      "Gợi ý 2..."
    ]
  }},
  "geo_score": 75.0 // Điểm Generative Engine Optimization (từ 0 đến 100)
}}

Hãy trả về phản hồi DUY NHẤT ở định dạng JSON thô. Không bọc trong dấu nháy markdown ```json hay thêm bất cứ văn bản giải thích nào khác.
"""
        try:
            model = genai.GenerativeModel(self.model_name)
            response = model.generate_content(
                prompt,
                generation_config={"response_mime_type": "application/json"}
            )
            
            result = json.loads(response.text)
            return result
        except Exception as e:
            print(f"[ERROR] LLM analysis failed: {str(e)}")
            return self._fallback_response(f"Lỗi khi gọi API Gemini: {str(e)}")

    def _fallback_response(self, message: str) -> dict:
        return {
            "eeat_score": 0.0,
            "eeat_feedback": {
                "expertise": message,
                "authoritativeness": "N/A",
                "trustworthiness": "N/A"
            },
            "aeo_score": 0.0,
            "aeo_feedback": {
                "direct_answer": "N/A",
                "faq_format": "N/A",
                "suggestions": [message]
            },
            "geo_score": 0.0
        }
