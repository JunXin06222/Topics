from flask import Flask, request, jsonify
from flask_cors import CORS
import google.generativeai as genai
import mysql.connector
import logging

app = Flask(__name__)
CORS(app)  # 啟用CORS支持

# 配置 Google Generative AI 服務
genai.configure(api_key="AIzaSyBjDPGhQMqOCwfy2trr0W_WEmkV4Tjr5yo")

# 初始化生成模型和對話
generation_config = {
    "temperature": 1,
    "top_p": 0.95,
    "top_k": 0,
    "max_output_tokens": 2048,
}

safety_settings = [
    {
        "category": "HARM_CATEGORY_HARASSMENT",
        "threshold": "BLOCK_MEDIUM_AND_ABOVE"
    },
    {
        "category": "HARM_CATEGORY_HATE_SPEECH",
        "threshold": "BLOCK_MEDIUM_AND_ABOVE"
    },
    {
        "category": "HARM_CATEGORY_SEXUALLY_EXPLICIT",
        "threshold": "BLOCK_MEDIUM_AND_ABOVE"
    },
    {
        "category": "HARM_CATEGORY_DANGEROUS_CONTENT",
        "threshold": "BLOCK_MEDIUM_AND_ABOVE"
    },
]

model = genai.GenerativeModel(model_name="gemini-1.5-pro-latest",
                              generation_config=generation_config,
                              safety_settings=safety_settings)

convo = model.start_chat(history=[])

# 提示詞
prompt = "請在回答中加入一周的飲食菜單和一周的健身菜單，並包含每項活動所獲得和消耗的熱量，請使用繁體中文，並且請每次都使用固定格式給我不要亂掉，但飲食和運動要有變化，記得格式都要一致"

# 資料庫連接
def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="00519",
        database="fitnessplatform"
    )

@app.route('/chat', methods=['POST'])
def chat():
    try:
        data = request.json
        user_input = data['user_input']
        height = data.get('height', 'N/A')
        weight = data.get('weight', 'N/A')
        dislikes = data.get('dislikes', 'N/A')
        goal = data.get('goal', 'N/A')
        user_id = data.get('user_id')

        additional_info = f"身高: {height} 公分, 體重: {weight} 公斤, 不喜歡吃的食物: {dislikes}, 目標: {goal}."
        complete_input = f"{user_input}\n\n{additional_info}\n\n{prompt}"

        # 发送用户输入给生成模型并获取回应
        response = convo.send_message(complete_input)
        response_text = response.text

        # 將資料存入資料庫
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute(
            "INSERT INTO ai_responses (user_id, user_input, height, weight, dislikes, goal, response) VALUES (%s, %s, %s, %s, %s, %s, %s)",
            (user_id, user_input, height, weight, dislikes, goal, response_text)
        )
        conn.commit()
        cursor.close()
        conn.close()

        return jsonify({'response': response_text})
    except Exception as e:
        app.logger.error(f"Error in /chat endpoint: {str(e)}")
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', debug=True, port=5000)
    