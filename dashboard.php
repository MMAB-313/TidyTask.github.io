<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['add_task']) && !empty($_POST['task_name'])) {
    $stmt = $pdo->prepare("INSERT INTO tasks (user_id, task_name, priority, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $_POST['task_name'], $_POST['priority']]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['done'])) {
    $stmt = $pdo->prepare("UPDATE tasks SET status='done' WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['done'], $user_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['save_session'])) {
    $study_name = !empty($_POST['study_name']) ? $_POST['study_name'] : 'Focus Session';
    $minutes = intval($_POST['minutes']);
    $stmt = $pdo->prepare("INSERT INTO study_logs (user_id, study_name, minutes, study_date) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $study_name, $minutes]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id=? AND status='pending' ORDER BY FIELD(priority, 'high','medium','low'), id DESC");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM study_logs WHERE user_id=? ORDER BY study_date DESC LIMIT 15");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TidyTasks | Precision Productivity Timer & Task Manager</title>
    
    <meta name="description" content="TidyTasks is a minimalist productivity tool featuring a Pomodoro-style focus timer and task manager to help you stay organized and efficient.">
    <meta name="keywords" content="productivity, timer, task manager, focus timer, pomodoro, study tool">
    <meta property="og:title" content="TidyTasks | Precision Productivity">
    <meta property="og:description" content="Stay focused and track your tasks with TidyTasks.">
    <meta property="og:type" content="website">
    
    <link rel="icon" href="favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root { --brand:#6366f1; --brand-glow:rgba(99,102,241,.4); --bg:#030712; }
        body{
            background:var(--bg);
            background-image:radial-gradient(circle at 50% -20%,#1e1b4b 0%,transparent 50%);
            color:#f9fafb;
            font-family:'Plus Jakarta Sans',sans-serif;
            min-height:100vh;
        }
        .glass-card{background:rgba(17,24,39,.7);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.08);}
        .nav-link.active{background:var(--brand);color:white;box-shadow:0 0 20px var(--brand-glow);}
        .timer-glow{text-shadow:0 0 60px var(--brand-glow);}
        .tab-content{display:none;}
        .tab-content.active{display:block;}
        #fs-overlay{display:none;z-index:9999;background:#030712; flex-direction: column;}
        #fs-overlay.active{display:flex;}
    </style>
</head>

<body class="flex flex-col lg:flex-row overflow-y-auto">

<aside class="w-full lg:w-72 flex flex-col p-4 lg:p-6 bg-black/40 lg:bg-black/20 border-b lg:border-b-0 lg:border-r border-white/5 sticky top-0 z-50">
    <div class="flex items-center justify-between lg:justify-start gap-3 px-2 lg:px-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
                <i class="ri-checkbox-circle-fill text-2xl text-white"></i>
            </div>
            <span class="text-xl font-extrabold block">TidyTasks</span>
        </div>
        <a href="logout.php" class="lg:hidden text-gray-400 hover:text-red-400">
            <i class="ri-logout-circle-line text-2xl"></i>
        </a>
    </div>

    <nav class="flex flex-row lg:flex-col gap-2 mt-4 lg:mt-10">
        <button onclick="switchTab('timer')" id="btn-timer" class="nav-link active flex-1 lg:flex-none px-4 py-3 rounded-2xl flex items-center justify-center lg:justify-start gap-3">
            <i class="ri-focus-3-line"></i>
            <span class="">Focus</span>
        </button>

        <button onclick="switchTab('history')" id="btn-history" class="nav-link flex-1 lg:flex-none px-4 py-3 rounded-2xl text-gray-400 hover:bg-white/5 flex items-center justify-center lg:justify-start gap-3">
            <i class="ri-bar-chart-box-line"></i>
            <span class="">Analytics</span>
        </button>
    </nav>

    <a href="logout.php" class="hidden lg:flex items-center gap-3 mt-auto px-4 py-3 text-gray-500 hover:text-red-400">
        <i class="ri-logout-circle-line"></i> Sign Out
    </a>
</aside>

<main class="flex-1 p-4 sm:p-6 lg:p-12">

    <section id="tab-timer" class="tab-content active">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 lg:gap-8">
            <div class="lg:col-span-3 glass-card rounded-3xl p-6 sm:p-10 text-center">
                <input id="study-name" placeholder="Enter Subject" class="bg-transparent text-indigo-400 text-center text-lg font-bold border-b border-white/10 mb-4 focus:outline-none">
                <div class="flex justify-center items-center gap-2 mb-4">
                    <input id="time-input" type="number" value="25" class="bg-transparent text-indigo-400 text-center text-2xl font-bold w-20 border-b border-white/5 focus:outline-none">
                    <span class="text-indigo-400 font-bold">min</span>
                </div>

                <div id="display" class="text-[4.5rem] sm:text-[6rem] lg:text-[10rem] font-extrabold timer-glow mb-6">25:00</div>

                <div class="flex flex-wrap gap-4 justify-center">
                    <button onclick="toggleTimer()" id="main-btn" class="flex-1 sm:flex-none px-8 py-4 bg-white text-black rounded-2xl font-bold transition hover:bg-indigo-50">
                        <i id="play-icon" class="ri-play-fill"></i> <span id="btn-text">Start Session</span>
                    </button>
                    <button onclick="resetTimer()" class="w-14 h-14 glass-card rounded-xl flex items-center justify-center hover:bg-white/10">
                        <i class="ri-restart-line"></i>
                    </button>
                    <button onclick="enterFS()" class="w-14 h-14 glass-card rounded-xl flex items-center justify-center hover:bg-white/10">
                        <i class="ri-fullscreen-line"></i>
                    </button>
                </div>
            </div>

            <div class="lg:col-span-2 flex flex-col gap-4">
                <form method="POST" class="glass-card p-2 rounded-xl flex gap-2">
                    <input name="task_name" required placeholder="Add task..." class="bg-transparent flex-1 px-3 focus:outline-none">
                    <select name="priority" class="bg-gray-800 rounded-lg px-2 text-xs">
                        <option value="high">High</option>
                        <option value="medium" selected>Med</option>
                        <option value="low">Low</option>
                    </select>
                    <button name="add_task" class="bg-indigo-600 w-10 h-10 rounded-lg flex items-center justify-center text-white">
                        <i class="ri-add-line"></i>
                    </button>
                </form>

                <div class="glass-card rounded-2xl p-4 max-h-[400px] overflow-y-auto">
                    <?php foreach($tasks as $t): ?>
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl mb-2">
                        <div class="flex items-center gap-3">
                            <a href="?done=<?= $t['id'] ?>" class="border-2 border-indigo-500 w-5 h-5 rounded hover:bg-indigo-500/20"></a>
                            <span><?= htmlspecialchars($t['task_name']) ?></span>
                        </div>
                        <span class="text-[10px] uppercase px-2 py-1 rounded-md bg-white/10"><?= $t['priority'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section id="tab-history" class="tab-content">
        <div class="max-w-3xl mx-auto glass-card rounded-3xl p-6">
            <h2 class="text-xl font-bold mb-6">Recent Sessions</h2>
            <?php foreach($history as $h): ?>
            <div class="flex justify-between p-4 bg-white/5 rounded-xl mb-3">
                <div>
                    <b><?= htmlspecialchars($h['study_name']) ?></b>
                    <p class="text-xs text-gray-400"><?= date('M d Y H:i', strtotime($h['study_date'])) ?></p>
                </div>
                <span class="text-indigo-400 font-bold">+<?= $h['minutes'] ?>m</span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<div id="fs-overlay" class="fixed inset-0 items-center justify-center">
    <button onclick="exitFS()" class="absolute top-10 right-10 text-white/40 hover:text-white text-3xl">
        <i class="ri-close-circle-line"></i>
    </button>
    <div id="fs-display" class="text-[35vw] font-black timer-glow leading-none">25:00</div>
</div>

<script>
let timer, timeLeft=1500, isRunning=false;
const display = document.getElementById('display');
const fsDisplay = document.getElementById('fs-display');
const timeInput = document.getElementById('time-input');
const studyName = document.getElementById('study-name');
const fsOverlay = document.getElementById('fs-overlay');

function switchTab(tab){
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.nav-link').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+tab).classList.add('active');
    document.getElementById('btn-'+tab).classList.add('active');
}

function update(){
    let m = Math.floor(timeLeft/60).toString().padStart(2,'0');
    let s = (timeLeft%60).toString().padStart(2,'0');
    display.innerText = fsDisplay.innerText = `${m}:${s}`;
}

function toggleTimer(){
    if(isRunning){
        clearInterval(timer);
        isRunning=false;
        document.getElementById('play-icon').className = 'ri-play-fill';
        document.getElementById('btn-text').innerText = 'Resume Session';
    } else {
        isRunning=true;
        document.getElementById('play-icon').className = 'ri-pause-fill';
        document.getElementById('btn-text').innerText = 'Pause Session';
        timer = setInterval(()=>{
            if(timeLeft > 0){ timeLeft--; update(); }
            else finish();
        }, 1000);
    }
}

function resetTimer(){
    clearInterval(timer);
    isRunning = false;
    timeLeft = timeInput.value * 60;
    document.getElementById('play-icon').className = 'ri-play-fill';
    document.getElementById('btn-text').innerText = 'Start Session';
    update();
}

async function finish(){
    clearInterval(timer);
    let fd = new FormData();
    fd.append('save_session', 1);
    fd.append('minutes', timeInput.value);
    fd.append('study_name', studyName.value || 'Focus Session');
    await fetch('', {method:'POST', body:fd});
    location.reload();
}

// FULLSCREEN LOGIC
function enterFS(){
    fsOverlay.classList.add('active');
    const elem = document.documentElement;
    if (elem.requestFullscreen) {
        elem.requestFullscreen();
    } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen();
    }
}

function exitFS() {
    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
    }
}

// Detection for exiting fullscreen via ESC key
document.addEventListener('fullscreenchange', () => {
    if (!document.fullscreenElement) {
        fsOverlay.classList.remove('active');
    }
});
document.addEventListener('webkitfullscreenchange', () => {
    if (!document.webkitFullscreenElement) {
        fsOverlay.classList.remove('active');
    }
});
</script>

</body>
</html>
