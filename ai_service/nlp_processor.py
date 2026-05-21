import re
from bs4 import BeautifulSoup
from underthesea import pos_tag, sent_tokenize

class NLPProcessor:
    @staticmethod
    def clean_html(html_content: str) -> str:
        """Loại bỏ thẻ HTML để lấy văn bản thuần"""
        if not html_content:
            return ""
        soup = BeautifulSoup(html_content, "html.parser")
        return soup.get_text(separator=" ")

    @staticmethod
    def analyze_pos(text: str) -> dict:
        """
        Phân tích nhãn từ loại (POS Tagging) để tính toán mật độ từ
        Trả về tỉ lệ Tính từ (A), Động từ (V), Danh từ (N) và đánh giá độ tự nhiên.
        """
        cleaned_text = NLPProcessor.clean_html(text)
        if not cleaned_text.strip():
            return {"adjective_density": 0, "verb_density": 0, "noun_density": 0, "readability": "Không có nội dung"}

        # Giới hạn tối đa 800 từ để tránh quá tải CPU local trên văn bản lớn
        words = cleaned_text.split()
        if len(words) > 800:
            cleaned_text = " ".join(words[:800])

        tagged = pos_tag(cleaned_text)
        total_words = len(tagged)

        if total_words == 0:
            return {"adjective_density": 0, "verb_density": 0, "noun_density": 0, "readability": "Không có nội dung"}

        adjectives = sum(1 for word, tag in tagged if tag.startswith('A'))
        verbs = sum(1 for word, tag in tagged if tag.startswith('V'))
        nouns = sum(1 for word, tag in tagged if tag.startswith('N'))

        adj_density = round(adjectives / total_words, 3)
        verb_density = round(verbs / total_words, 3)
        noun_density = round(nouns / total_words, 3)

        # Đánh giá sơ bộ độ đọc dễ (Readability)
        # Tỉ lệ danh từ quá cao thường thể hiện văn bản học thuật/kỹ thuật khó đọc.
        # Tỉ lệ tính từ cao (>0.15) có xu hướng quảng cáo quá nhiều.
        if noun_density > 0.45:
            readability = "Khá phức tạp (Nhiều thuật ngữ/danh từ)"
        elif adj_density > 0.18:
            readability = "Mang tính quảng cáo cao (Nhiều tính từ mô tả)"
        else:
            readability = "Dễ tiếp cận & Tự nhiên"

        return {
            "adjective_density": adj_density,
            "verb_density": verb_density,
            "noun_density": noun_density,
            "readability": readability
        }

    @staticmethod
    def extract_semantic_triples(text: str, max_triples: int = 5) -> list:
        """
        Trích xuất các khẳng định cốt lõi dạng Subject - Relation - Object
        bằng cách phân tích cấu trúc POS trong các câu ngắn.
        """
        cleaned_text = NLPProcessor.clean_html(text)
        sentences = sent_tokenize(cleaned_text)
        triples = []

        for sentence in sentences:
            if len(triples) >= max_triples:
                break
            
            tagged = pos_tag(sentence)
            # Tìm danh từ đầu tiên làm Subject, động từ đầu tiên sau đó làm Relation, 
            # và danh từ tiếp theo làm Object
            subject = None
            relation = None
            obj = None

            for word, tag in tagged:
                # Lọc bỏ ký tự đặc biệt
                word_clean = re.sub(r'[^\w\s]', '', word).strip()
                if not word_clean:
                    continue

                if tag.startswith('N') and not subject and not relation:
                    subject = word_clean
                elif tag.startswith('V') and subject and not relation:
                    relation = word_clean
                elif tag.startswith('N') and subject and relation and not obj:
                    obj = word_clean
                    break

            if subject and relation and obj:
                triples.append([subject, relation, obj])

        # Fallback nếu không bóc tách được bằng cấu trúc cơ bản
        if not triples and sentences:
            # Lấy tiêu đề hoặc câu đầu tiên làm Triple mẫu
            words = cleaned_text.split()
            if len(words) >= 3:
                triples.append(["Nội dung chính", "chủ đề", " ".join(words[:4])])

        return triples

    @staticmethod
    def warmup():
        """Chạy mồi một câu ngắn để tải sẵn mô hình học máy vào RAM khi khởi động server"""
        try:
            print("[INFO] Warm-up Vietnamese NLP Model (Underthesea)...")
            pos_tag("Khởi chạy hệ thống phân tích AI.")
            print("[INFO] NLP Model ready.")
        except Exception as e:
            print(f"[WARNING] NLP Warm-up failed: {str(e)}")
