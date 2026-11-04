<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nosotros - itrio team</title>
    <style>
        :root {
            --primary-color: #151b1f;
            --secondary-color: #1e293b;
            --bg-color: #f4f5f7;
            --card-bg: #ffffff;
            --border-color: #cbd5e1;
            --text-main: #1e293b;
            --text-muted: #475569;
            --header-text: #e2e8f0;
        }

        /* Variables para el Modo Oscuro (Inversión de colores) */
        [data-theme="dark"] {
            --primary-color: #1e293b;
            --secondary-color: #f8fafc;
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --border-color: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --header-text: #ffffff;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            transition: background-color 0.3s ease, color 0.3s ease;
            animation: fadeIn 0.8s ease-out forwards;
        }

        /* Animación suave de apertura de página */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        header {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 2.5rem 1rem;
            border-bottom: 4px solid #002244;
            position: relative;
            transition: background-color 0.3s ease;
        }

        /* Botón de Modo Oscuro */
        .theme-toggle {
            position: absolute;
            top: 1.2rem;
            right: 1.5rem;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background 0.2s;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .header-logo-container {
            margin-bottom: -2.5rem;
        }

        .header-logo {
            max-height: 300px;
            width: auto;
            object-fit: contain;
            display: inline-block;
        }

        header p {
            color: var(--header-text);
            font-size: 1.1rem;
            margin: 0;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .container {
            max-width: 1250px;
            margin: -2rem auto 3rem auto;
            padding: 0 1.5rem;
        }

        .card-intro {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 2.5rem;
            text-align: center;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .card-intro h2 {
            color: var(--secondary-color);
            margin-top: 0;
            font-size: 1.75rem;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
        }

        .team-member {
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            text-align: center;
            padding: 2rem 1.25rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.3s ease, border-color 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .member-photo {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1.2rem auto;
            background-color: #e2e8f0;
            border: 3px solid var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            overflow: hidden;
        }

        .member-photo img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .member-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin: 0 0 0.3rem 0;
        }

        .member-role {
            color: var(--secondary-color);
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.4;
            opacity: 0.9;
        }

        .member-bio {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0;
        }

        /* Cartel de Contrataciones / Contacto */
        .hire-banner {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-left: 5px solid var(--primary-color);
            border-radius: 8px;
            padding: 1.5rem 2rem;
            margin-top: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .hire-info h3 {
            margin: 0 0 0.25rem 0;
            color: var(--secondary-color);
            font-size: 1.2rem;
        }

        .hire-info p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .hire-email {
            background-color: var(--primary-color);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: opacity 0.2s;
        }

        .hire-email:hover {
            opacity: 0.9;
        }

        footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            border-top: 1px solid var(--border-color);
            margin-top: 3rem;
            background-color: var(--card-bg);
            transition: background-color 0.3s ease;
        }
    </style>
</head>
<body>

    <header>
        <!-- Botón para alternar el Modo Oscuro -->
        <button class="theme-toggle" id="themeToggleBtn" onclick="toggleTheme()">🌙 Modo Oscuro</button>

        <div class="header-logo-container">
            <img src="../Material_Visual/itrio.png" alt="itrio team" class="header-logo">
        </div>
        <p>Innovación, desarrollo de software y tecnología educativa</p>
    </header>

    <div class="container">
        <!-- Sección descriptiva institucional -->
        <div class="card-intro">
            <h2>¿Quiénes somos?</h2>
            <p>Somos un colectivo enfocado en el desarrollo de soluciones tecnológicas eficientes, orientadas a optimizar la gestión institucional y la experiencia de usuario en entornos educativos. A través de herramientas modernas y código robusto, impulsamos sistemas de control avanzados vinculados al CeiLab.</p>
        </div>

        <!-- Título de sección de integrantes -->
        <h2 style="text-align: center; margin-bottom: 1.5rem; color: var(--secondary-color);">Integrantes del Equipo</h2>
        
        <div class="team-grid">
            
            <!-- 1. Lucas Pereira -->
            <div class="team-member">
                <div class="member-photo">
                    <img src="../Material_Visual/assets/img/miembro1.jpeg" alt="Lucas Pereira" onerror="this.style.display='none'; this.parentElement.innerHTML='Sin foto';">
                </div>
                <h3 class="member-name">Lucas Pereira</h3>
                <div class="member-role">Coordinador / Documentador</div>
                <p class="member-bio">Experto en coordinación de proyectos y documentación técnica.</p>
            </div>

            <!-- 2. Matías Indart -->
            <div class="team-member">
                <div class="member-photo">
                    <img src="../Material_Visual/assets/img/miembro2.jpeg" alt="Matías Indart" onerror="this.style.display='none'; this.parentElement.innerHTML='Sin foto';">
                </div>
                <h3 class="member-name">Matías Indart</h3>
                <div class="member-role">Sub-Coordinador / Programador Backend y Frontend</div>
                <p class="member-bio">Especialista en desarrollo backend y frontend, con énfasis en la creación de soluciones escalables y eficientes.</p>
            </div>

            <!-- 3. José Rodríguez -->
            <div class="team-member">
                <div class="member-photo">
                    <img src="../Material_Visual/assets/img/miembro3.jpeg" alt="José Rodríguez" onerror="this.style.display='none'; this.parentElement.innerHTML='Sin foto';">
                </div>
                <h3 class="member-name">José Rodríguez</h3>
                <div class="member-role">Diseñador y Administrador de Base de Datos / Programador frontend y backend</div>
                <p class="member-bio">Responsable de la arquitectura de la base de datos relacional, además del desarrollo del frontend y lógica backend.</p>
            </div>

            <!-- 4. Cuarto Integrante -->
            <div class="team-member">
                <div class="member-photo">
                    <img src="../Material_Visual/assets/img/miembro4.jpeg" alt="Valentín Fernández" onerror="this.style.display='none'; this.parentElement.innerHTML='Sin foto';">
                </div>
                <h3 class="member-name">Valentín Fernández</h3>
                <div class="member-role">Programador Frontend y Backend / Debugger</div>
                <p class="member-bio">Enfocado en el desarrollo full stack, depuración de sistemas, documentación técnica y control de versiones.</p>
            </div>

        </div>

        <!-- Cartel de Contacto para Contrataciones -->
        <div class="hire-banner">
            <div class="hire-info">
                <h3>¿Interesado en nuestros servicios o desarrollos a medida?</h3>
                <p>Contáctanos para propuestas, consultas institucionales o contratación de proyectos.</p>
            </div>
            <a href="mailto:contacto@itrioteam.uy" class="hire-email">✉ contacto@itrioteam.uy</a>
        </div>
    </div>

    <footer>
        &copy; 2026 itrio team — Sistema de Gestión CeiLab. Todos los derechos reservados.
    </footer>

    <!-- Script JavaScript para manejar el Modo Oscuro -->
    <script>
        const htmlElement = document.documentElement;
        const themeBtn = document.getElementById('themeToggleBtn');

        function toggleTheme() {
            if (htmlElement.getAttribute('data-theme') === 'dark') {
                htmlElement.removeAttribute('data-theme');
                themeBtn.textContent = '🌙 Modo Oscuro';
                localStorage.setItem('theme', 'light');
            } else {
                htmlElement.setAttribute('data-theme', 'dark');
                themeBtn.textContent = '☀️ Modo Claro';
                localStorage.setItem('theme', 'dark');
            }
        }

        // Mantener la preferencia guardada al cambiar de página
        if (localStorage.getItem('theme') === 'dark') {
            htmlElement.setAttribute('data-theme', 'dark');
            themeBtn.textContent = '☀️ Modo Claro';
        }
    </script>

</body>
</html>