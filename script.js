// Definicije elemenata
const questionElement       = document.getElementById("question");
const answerButtons         = document.querySelectorAll(".answer-btn");
const hintButton            = document.getElementById("hint-btn");
const hintElement           = document.getElementById("hint");
const nextButton            = document.getElementById("next-button");
const scoreElement          = document.getElementById("score");
const questionNumberElement = document.getElementById("question-number");
const questionImageContainer = document.getElementById("question-image-container");
const questionImage         = document.getElementById("question-image");

// Varijable za kviz
let questions             = [];
let currentQuestionIndex  = 0;
let score                 = 0;
let answered              = false;

// Učitavanje pitanja s index.php
async function loadQuestionsFromAPI() {
    try {
        const response = await fetch("index.php?getQuestions=1");
        if (!response.ok) {
            console.error("Greška u odgovoru servera:", response.status, response.statusText);
            questionElement.innerText = "Greška pri učitavanju podataka (status)!";
            return;
        }

        questions = await response.json();
        console.log("Dohvaćeni podaci (cijeli JSON):", questions);

        if (questions.error) {
            questionElement.innerText = "Greška pri učitavanju podataka: " + questions.error;
            return;
        }

        // Debug: Ispiši sva pitanja
        questions.forEach((q, i) => {
            console.log(`[Debug] Pitanje #${i+1}:`, q);
        });

        loadQuestion();
    } catch (error) {
        questionElement.innerText = "Greška na serveru!";
        console.error("Pogreška prilikom dohvata pitanja:", error);
    }
}

// Učitavanje jednog pitanja
function loadQuestion() {
    resetState();
    const currentQuestion = questions[currentQuestionIndex];
    console.log(`[Debug] Učitavam pitanje indeks: ${currentQuestionIndex}`, currentQuestion);

    // Postavi tekst pitanja
    questionElement.innerText = currentQuestion.question;
    
    // Provjera postoji li slika i postavljanje njezinog prikaza
    if (currentQuestion.image && currentQuestion.image.trim() !== "") {
        questionImage.src = currentQuestion.image;
        questionImageContainer.style.display = "block";
    } else {
        questionImageContainer.style.display = "none";
    }

    // Obrada odgovora (odvojeni znakom "|")
    let possibleAnswers = currentQuestion.answers.split("|");
    let correctAnswerIndex = parseInt(currentQuestion.correctAnswer, 10);
    console.log(`[Debug] possibleAnswers=`, possibleAnswers);
    console.log(`[Debug] correctAnswerIndex=`, correctAnswerIndex);

    answerButtons.forEach((button, index) => {
        if (possibleAnswers[index]) {
            button.style.display = "block";
            button.innerText = possibleAnswers[index];
            button.dataset.correct = (index === correctAnswerIndex) ? "true" : "false";
            console.log(`Gumb #${index} = ${possibleAnswers[index]}, correct=${button.dataset.correct}`);
        } else {
            button.style.display = "none";
        }
    });

    questionNumberElement.innerText = `Pitanje ${currentQuestionIndex + 1} / ${questions.length}`;
    hintElement.innerText = currentQuestion.hint || "Nema savjeta za ovo pitanje.";
    hintElement.style.display = "none";
    hintButton.innerText = "Prikaži savjet";
}

// Prekidač za prikaz/sakrivanje savjeta
function toggleHint() {
    if (hintElement.style.display === "none") {
        hintElement.style.display = "block";
        hintButton.innerText = "Sakrij savjet";
    } else {
        hintElement.style.display = "none";
        hintButton.innerText = "Prikaži savjet";
    }
}

// Resetiranje stanja prije učitavanja novog pitanja
function resetState() {
    answered = false;
    nextButton.disabled = true;
    answerButtons.forEach(button => {
        button.classList.remove("correct", "incorrect");
        button.disabled = false;
    });
}

// Funkcija koja se poziva kada korisnik klikne na odgovor
function selectAnswer(e) {
    if (answered) return;
    const selectedButton = e.target;
    const isCorrect = selectedButton.dataset.correct === "true";
    console.log("[Debug] Kliknuo si:", selectedButton.innerText, " -> isCorrect?", isCorrect);

    if (isCorrect) {
        selectedButton.classList.add("correct");
        score++;
    } else {
        selectedButton.classList.add("incorrect");
        // Označi točan odgovor
        answerButtons.forEach(button => {
            if (button.dataset.correct === "true") {
                button.classList.add("correct");
            }
        });
    }

    // Onemogući daljnje klikanje
    answerButtons.forEach(button => { button.disabled = true; });
    updateScoreDisplay();
    nextButton.disabled = false;
    answered = true;
}

// Funkcija za učitavanje sljedećeg pitanja
function nextQuestion() {
    currentQuestionIndex++;
    if (currentQuestionIndex < questions.length) {
        loadQuestion();
    } else {
        questionElement.innerText = "Kviz završen!";
        nextButton.style.display = "none";
        hintButton.style.display = "none";
        answerButtons.forEach(button => button.style.display = "none");
    }
}

// Ažuriranje prikaza rezultata
function updateScoreDisplay() {
    scoreElement.innerText = `Bodovi: ${score} / ${currentQuestionIndex + 1}`;
}

// Postavljanje event listenera
answerButtons.forEach(button => button.addEventListener("click", selectAnswer));
nextButton.addEventListener("click", nextQuestion);
hintButton.addEventListener("click", toggleHint);

// Učitavanje pitanja pri pokretanju
loadQuestionsFromAPI();
