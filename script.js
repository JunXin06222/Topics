document.getElementById('qaForm').addEventListener('submit', function(event) {
    event.preventDefault();
    var question = document.getElementById('question').value;
    var height = document.getElementById('height').value;
    var weight = document.getElementById('weight').value;
    var dislikes = document.getElementById('dislikes').value;
    var goal = document.getElementById('goal').value;

    var askButton = document.querySelector('button[type="submit"]');
    var buttonDisplayStyle = askButton.style.display;

    askButton.style.display = 'none';
    document.getElementById('answer').innerHTML = '載入中...';

    fetch('https://b620-163-17-135-93.ngrok-free.app/chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_input: question,
            height: height,
            weight: weight,
            dislikes: dislikes,
            goal: goal
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('answer').innerHTML = formatAIResponse(data.response);
        
        // 保存資料到資料庫
        return fetch('/save_response.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_input: question,
                height: height,
                weight: weight,
                dislikes: dislikes,
                goal: goal,
                response: data.response
            })
        });
    })
    .then(saveResponse => saveResponse.json())
    .then(saveData => {
        if (saveData.status === 'success') {
            console.log('資料已成功存儲');
        } else {
            console.error('資料存儲失敗: ' + saveData.message);
        }
        askButton.style.display = buttonDisplayStyle;
    })
    .catch(error => {
        console.error('錯誤:', error);
        askButton.style.display = buttonDisplayStyle;
    });
});