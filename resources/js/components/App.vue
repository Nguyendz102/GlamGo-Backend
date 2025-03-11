<template>
    <div class="glamgo-container">
        <div class="hero">
            <h1 class="hero-title">Chào mừng đến với GlamGo</h1>
            <p class="hero-subtitle">Nơi phong cách gặp gỡ công nghệ</p>
            <button @click="startExploring" class="cta-button">Bắt đầu ngay</button>
        </div>
    </div>
</template>

<script setup>
const startExploring = () => {
    alert('Chào mừng bạn đến với GlamGo! Hãy khám phá ngay.');
};
</script>

<style scoped>
.glamgo-container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
    background: linear-gradient(135deg, #ff9a9e, #fad0c4);
    color: white;
    text-align: center;
}

.hero {
    background: rgba(255, 255, 255, 0.2);
    padding: 40px;
    border-radius: 20px;
    backdrop-filter: blur(15px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.hero-title {
    font-size: 3em;
    font-weight: bold;
    animation: fadeIn 1s ease-in-out;
}

.hero-subtitle {
    font-size: 1.5em;
    margin-bottom: 20px;
}

.cta-button {
    background: white;
    color: #ff758c;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1.2em;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
}

.cta-button:hover {
    background: #f8a5c2;
    transform: scale(1.05);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>