from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from nlp_processor import NLPProcessor
from llm_client import LLMClient
import config

app = FastAPI(
    title="AI Content SEO Auditor Service",
    description="Python NLP and LLM Service for kientruc CMS",
    version="1.0.0"
)

# Cho phép CORS từ localhost
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Khởi tạo LLM Client
llm_client = LLMClient()

# Khởi chạy warm-up mô hình NLP Tiếng Việt local để tránh lag ở request đầu tiên
NLPProcessor.warmup()

class ArticleRequest(BaseModel):
    title: str
    content: str

@app.get("/")
def read_root():
    return {"status": "running", "service": "AI SEO Auditor"}

@app.post("/analyze")
def analyze_article(article: ArticleRequest):
    """
    Tiến hành phân tích bài viết bằng kết hợp NLP truyền thống và Generative AI.
    """
    if not article.content.strip():
        raise HTTPException(status_code=400, detail="Content cannot be empty")

    # 1. Chạy NLP local (POS Tagging & Readability & Triples)
    try:
        nlp_metrics = NLPProcessor.analyze_pos(article.content)
        semantic_triples = NLPProcessor.extract_semantic_triples(article.content)
    except Exception as e:
        print(f"[ERROR] Local NLP processing failed: {str(e)}")
        nlp_metrics = {"adjective_density": 0.0, "verb_density": 0.0, "noun_density": 0.0, "readability": "Lỗi xử lý NLP"}
        semantic_triples = []

    # 2. Gọi AI đánh giá E-E-A-T, AEO, GEO
    try:
        ai_evaluation = llm_client.analyze_content(article.title, article.content)
    except Exception as e:
        print(f"[ERROR] LLM evaluation failed: {str(e)}")
        ai_evaluation = {
            "eeat_score": 0.0,
            "eeat_feedback": {"expertise": "Lỗi gọi LLM", "authoritativeness": "N/A", "trustworthiness": "N/A"},
            "aeo_score": 0.0,
            "aeo_feedback": {"direct_answer": "N/A", "faq_format": "N/A", "suggestions": ["Không thể liên lạc với LLM"]},
            "geo_score": 0.0
        }

    # 3. Gom và trả kết quả tổng hợp
    return {
        "eeat_score": ai_evaluation.get("eeat_score", 0.0),
        "eeat_feedback": ai_evaluation.get("eeat_feedback", {}),
        "aeo_score": ai_evaluation.get("aeo_score", 0.0),
        "aeo_feedback": ai_evaluation.get("aeo_feedback", {}),
        "geo_score": ai_evaluation.get("geo_score", 0.0),
        "nlp_metrics": nlp_metrics,
        "semantic_triples": semantic_triples
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host=config.HOST, port=config.PORT, reload=True)
