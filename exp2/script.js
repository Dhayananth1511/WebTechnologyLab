const validChoices = ["rock", "paper", "scissors"];
    const icons = {
        rock: "✊",
        paper: "✋",
        scissors: "✌"
    };

    let userScore = 0;
    let cpuScore = 0;
    const WIN_SCORE = 10;
    let gameOver = false;

    function getComputerChoice() {
        return validChoices[Math.floor(Math.random() * validChoices.length)];
    }

    function normalizeChoice(choice) {
        return choice.toLowerCase().trim();
    }

    function validateChoice(choice) {
        const cleaned = normalizeChoice(choice);
        return validChoices.includes(cleaned) ? cleaned : null;
    }

    function playFromInput() {
        if (gameOver) return;

        const input = document.getElementById("input");
        const error = document.getElementById("error");
        const userChoice = validateChoice(input.value);

        if (!userChoice) {
            error.textContent = "Enter only rock, paper, or scissors.";
            return;
        }

        error.textContent = "";
        play(userChoice);
    }

    function play(userChoice) {
        if (gameOver) return;

        const cpuChoice = getComputerChoice();
        animateRound(userChoice, cpuChoice);
    }

    function animateRound(userChoice, cpuChoice) {
        const arena = document.getElementById("arenaBox");
        const statusMsg = document.getElementById("statusMsg");
        const userEmoji = document.getElementById("userEmoji");
        const cpuEmoji = document.getElementById("cpuEmoji");
        const userLabel = document.getElementById("userChoiceLabel");
        const cpuLabel = document.getElementById("cpuChoiceLabel");

        arena.classList.add("shake");
        statusMsg.textContent = "Thinking...";
        statusMsg.className = "status subtle";

        userEmoji.textContent = "⏳";
        cpuEmoji.textContent = "⏳";
        userLabel.textContent = "Your choice";
        cpuLabel.textContent = "Computer";

        setTimeout(() => {
            arena.classList.remove("shake");

            userEmoji.textContent = icons[userChoice];
            cpuEmoji.textContent = icons[cpuChoice];
            userLabel.textContent = userChoice;
            cpuLabel.textContent = cpuChoice;

            const result = determineWinner(userChoice, cpuChoice);
            updateUI(result);
            updateProgress();
            checkGameEnd();
        }, 550);
    }

    function determineWinner(user, cpu) {
        if (user === cpu) return "draw";

        if (
            (user === "rock" && cpu === "scissors") ||
            (user === "paper" && cpu === "rock") ||
            (user === "scissors" && cpu === "paper")
        ) {
            return "user";
        }

        return "cpu";
    }

    function updateUI(result) {
        const statusMsg = document.getElementById("statusMsg");
        const userScoreEl = document.getElementById("userScore");
        const cpuScoreEl = document.getElementById("cpuScore");

        if (result === "draw") {
            statusMsg.textContent = "It is a draw.";
            statusMsg.className = "status subtle pop";
        } else if (result === "user") {
            userScore++;
            userScoreEl.textContent = userScore;
            statusMsg.textContent = "You won this round!";
            statusMsg.className = "status pop";
        } else {
            cpuScore++;
            cpuScoreEl.textContent = cpuScore;
            statusMsg.textContent = "Computer won this round.";
            statusMsg.className = "status pop";
        }
    }

    function updateProgress() {
        const progressBar = document.getElementById("progressBar");
        const maxScore = Math.max(userScore, cpuScore);
        const progress = Math.min((maxScore / WIN_SCORE) * 100, 100);
        progressBar.style.width = progress + "%";
    }

    function checkGameEnd() {
        const banner = document.getElementById("winnerBanner");
        const buttons = document.getElementById("choiceButtons").querySelectorAll("button");
        const playButton = document.querySelector(".play-btn");

        if (userScore >= WIN_SCORE) {
            gameOver = true;
            banner.textContent = "🏆 You reached 10 points and won the game!";
            banner.classList.add("show");
            disableControls(buttons, playButton);
            document.getElementById("statusMsg").textContent = "Game over. You win!";
        } else if (cpuScore >= WIN_SCORE) {
            gameOver = true;
            banner.textContent = "💀 Computer reached 10 points and won the game!";
            banner.classList.add("show");
            disableControls(buttons, playButton);
            document.getElementById("statusMsg").textContent = "Game over. Computer wins!";
        }
    }

    function disableControls(buttons, playButton) {
        buttons.forEach(btn => btn.disabled = true);
        playButton.disabled = true;
        document.getElementById("input").disabled = true;
    }

    function enableControls() {
        const buttons = document.getElementById("choiceButtons").querySelectorAll("button");
        const playButton = document.querySelector(".play-btn");
        buttons.forEach(btn => btn.disabled = false);
        playButton.disabled = false;
        document.getElementById("input").disabled = false;
    }

    function resetGame() {
        userScore = 0;
        cpuScore = 0;
        gameOver = false;

        document.getElementById("userScore").textContent = "0";
        document.getElementById("cpuScore").textContent = "0";
        document.getElementById("userEmoji").textContent = "❔";
        document.getElementById("cpuEmoji").textContent = "❔";
        document.getElementById("userChoiceLabel").textContent = "Your choice";
        document.getElementById("cpuChoiceLabel").textContent = "Computer";
        document.getElementById("statusMsg").textContent = "Choose rock, paper, or scissors.";
        document.getElementById("statusMsg").className = "status subtle";
        document.getElementById("error").textContent = "";
        document.getElementById("input").value = "";
        document.getElementById("progressBar").style.width = "0%";
        document.getElementById("winnerBanner").classList.remove("show");
        enableControls();
    }

    document.getElementById("input").addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            playFromInput();
        }
    });