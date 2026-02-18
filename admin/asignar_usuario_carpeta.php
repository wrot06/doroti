<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Nombre Banda</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            background: #000;
            height: 100vh;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial
        }

        canvas {
            position: absolute;
            top: 0;
            left: 0
        }

        #logo {
            width: 320px;
            cursor: pointer;
            filter: brightness(.8) contrast(1.2) drop-shadow(0 0 30px rgba(255, 0, 0, .4));
            position: relative;
            z-index: 2
        }

        .glitch {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 320px;
            opacity: .15;
            mix-blend-mode: screen;
            z-index: 1
        }
    </style>
</head>

<body>

    <canvas id="bg"></canvas>
    <img src="logo.png" id="logo">
    <img src="logo.png" class="glitch">

    <script>
        // PARTICULAS OSCURAS
        const canvas = document.getElementById("bg");
        const ctx = canvas.getContext("2d");
        canvas.width = innerWidth;
        canvas.height = innerHeight;
        let particles = [];
        for (let i = 0; i < 120; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                r: Math.random() * 1.5 + 0.5,
                s: Math.random() * 0.5
            });
        }

        function animateParticles() {
            ctx.fillStyle = "rgba(0,0,0,0.2)";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = "rgba(120,0,0,0.5)";
            particles.forEach(p => {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fill();
                p.y += p.s;
                if (p.y > canvas.height) p.y = 0;
            });
            requestAnimationFrame(animateParticles);
        }
        animateParticles();

        // PULSO OSCURO LOGO
        anime({
            targets: '#logo',
            scale: [1, 1.05],
            duration: 4000,
            direction: 'alternate',
            loop: true,
            easing: 'easeInOutSine'
        });

        // EFECTO GLITCH
        anime({
            targets: '.glitch',
            translateX: [-5, 5],
            duration: 100,
            direction: 'alternate',
            loop: true,
            easing: 'steps(2)'
        });

        // CLICK → ENLACE
        const links = [
            "https://tulink1.com",
            "https://tulink2.com"
        ];
        let i = 0;
        document.getElementById("logo").addEventListener("click", () => {
            window.open(links[i], "_blank");
            i = (i + 1) % links.length;
        });
    </script>

</body>

</html>