<?php
/**
 * INSTALADOR AUTOMÁTICO - PORTFOLIO DE FOTÓGRAFO
 * 
 * Este script configura automaticamente o portfolio de fotógrafo
 * Execute uma vez e depois delete este arquivo por segurança
 * 
 * Versão: 2.0.0
 * Data: Agosto 2025
 */

// Configurações de segurança
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar se já foi instalado
if (file_exists('.installed')) {
    die('⚠️ Sistema já foi instalado! Delete o arquivo ".installed" se quiser reinstalar.');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📸 Instalação - Portfolio de Fotógrafo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; 
            padding: 20px;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header { 
            background: #2c3e50; 
            color: white; 
            padding: 30px; 
            text-align: center; 
        }
        .header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .header p { opacity: 0.8; font-size: 1.1rem; }
        .content { padding: 40px; }
        .step { 
            margin-bottom: 30px; 
            padding: 20px; 
            border: 2px solid #ecf0f1; 
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .step.active { border-color: #3498db; background: #f8f9fa; }
        .step.success { border-color: #27ae60; background: #d5f4e6; }
        .step.error { border-color: #e74c3c; background: #fdf2f2; }
        .step h3 { color: #2c3e50; margin-bottom: 15px; display: flex; align-items: center; }
        .step-icon { margin-right: 10px; font-size: 1.2rem; }
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #2c3e50; 
        }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #ecf0f1; 
            border-radius: 6px; 
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus, .form-group select:focus { 
            outline: none; 
            border-color: #3498db; 
        }
        .btn { 
            background: #3498db; 
            color: white; 
            padding: 15px 30px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 1.1rem; 
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn:hover { background: #2980b9; transform: translateY(-2px); }
        .btn:disabled { background: #bdc3c7; cursor: not-allowed; transform: none; }
        .success-msg { 
            background: #d5f4e6; 
            color: #27ae60; 
            padding: 20px; 
            border-radius: 6px; 
            margin: 20px 0; 
            border-left: 4px solid #27ae60;
        }
        .error-msg { 
            background: #fdf2f2; 
            color: #e74c3c; 
            padding: 20px; 
            border-radius: 6px; 
            margin: 20px 0; 
            border-left: 4px solid #e74c3c;
        }
        .info-msg { 
            background: #e3f2fd; 
            color: #1976d2; 
            padding: 20px; 
            border-radius: 6px; 
            margin: 20px 0; 
            border-left: 4px solid #1976d2;
        }
        .progress { 
            width: 100%; 
            height: 4px; 
            background: #ecf0f1; 
            border-radius: 2px; 
            overflow: hidden;
            margin-bottom: 30px;
        }
        .progress-bar { 
            height: 100%; 
            background: linear-gradient(90deg, #3498db, #2ecc71); 
            width: 0%; 
            transition: width 0.5s ease;
        }
        .checklist { list-style: none; }
        .checklist li { 
            padding: 10px 0; 
            border-bottom: 1px solid #ecf0f1; 
            display: flex; 
            align-items: center;
        }
        .checklist li:last-child { border-bottom: none; }
        .check-icon { margin-right: 15px; font-size: 1.2rem; }
        .check-icon.success { color: #27ae60; }
        .check-icon.error { color: #e74c3c; }
        .check-icon.pending { color: #bdc3c7; }
        .footer { 
            background: #34495e; 
            color: white; 
            padding: 20px 40px; 
            text-align: center; 
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📸 Portfolio de Fotógrafo</h1>
            <p>Instalação Automática v2.0</p>
        </div>

        <div class="content">
            <div class="progress">
                <div class="progress-bar" id="progressBar"></div>
            </div>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <?php
                // Processar instalação
                $errors = [];
                $success = [];
                $progress = 0;

                // Validar dados do formulário
                $db_host = $_POST['db_host'] ?? 'localhost';
                $db_name = $_POST['db_name'] ?? 'photographer_portfolio';
                $db_user = $_POST['db_user'] ?? 'root';
                $db_pass = $_POST['db_pass'] ?? '';
                $admin_pass = $_POST['admin_pass'] ?? 'admin123';
                $site_name = $_POST['site_name'] ?? 'Fotógrafo Portfólio';
                $site_email = $_POST['site_email'] ?? 'contato@seusite.com';

                // Teste de conexão com banco
                try {
                    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $success[] = "✅ Conexão com MySQL estabelecida";
                    $progress += 20;
                } catch (Exception $e) {
                    $errors[] = "❌ Erro de conexão MySQL: " . $e->getMessage();
                }

                // Criar banco de dados
                if (empty($errors)) {
                    try {
                        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $pdo->exec("USE `$db_name`");
                        $success[] = "✅ Banco de dados '$db_name' criado/selecionado";
                        $progress += 20;
                    } catch (Exception $e) {
                        $errors[] = "❌ Erro ao criar banco: " . $e->getMessage();
                    }
                }

                // Executar SQL de estrutura
                if (empty($errors) && file_exists('database/setup.sql')) {
                    try {
                        $sql = file_get_contents('database/setup.sql');
                        // Executar comandos SQL
                        $statements = explode(';', $sql);
                        foreach ($statements as $statement) {
                            $statement = trim($statement);
                            if (!empty($statement)) {
                                $pdo->exec($statement);
                            }
                        }
                        $success[] = "✅ Estrutura do banco de dados criada";
                        $progress += 20;
                    } catch (Exception $e) {
                        $errors[] = "❌ Erro ao executar SQL: " . $e->getMessage();
                    }
                }

                // Criar arquivo .env
                if (empty($errors)) {
                    try {
                        $env_content = "# Configurações geradas pela instalação\n";
                        $env_content .= "DB_HOST=$db_host\n";
                        $env_content .= "DB_NAME=$db_name\n";
                        $env_content .= "DB_USER=$db_user\n";
                        $env_content .= "DB_PASS=$db_pass\n";
                        $env_content .= "ADMIN_PASSWORD=$admin_pass\n";
                        $env_content .= "SITE_NAME=$site_name\n";
                        $env_content .= "SITE_EMAIL=$site_email\n";
                        $env_content .= "INSTALLED_DATE=" . date('Y-m-d H:i:s') . "\n";

                        file_put_contents('.env', $env_content);
                        $success[] = "✅ Arquivo de configuração .env criado";
                        $progress += 20;
                    } catch (Exception $e) {
                        $errors[] = "❌ Erro ao criar .env: " . $e->getMessage();
                    }
                }

                // Criar pastas necessárias
                if (empty($errors)) {
                    try {
                        if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                        if (!is_dir('uploads/thumbnails')) mkdir('uploads/thumbnails', 0755, true);
                        if (!is_dir('cache')) mkdir('cache', 0755, true);
                        
                        $success[] = "✅ Pastas necessárias criadas";
                        $progress += 10;
                    } catch (Exception $e) {
                        $errors[] = "❌ Erro ao criar pastas: " . $e->getMessage();
                    }
                }

                // Marcar como instalado
                if (empty($errors)) {
                    try {
                        file_put_contents('.installed', date('Y-m-d H:i:s'));
                        $success[] = "✅ Instalação concluída com sucesso!";
                        $progress = 100;
                    } catch (Exception $e) {
                        $errors[] = "❌ Erro ao finalizar instalação: " . $e->getMessage();
                    }
                }
                ?>

                <script>
                    document.getElementById('progressBar').style.width = '<?= $progress ?>%';
                </script>

                <?php if (!empty($success)): ?>
                    <div class="success-msg">
                        <h4>✅ Instalação realizada com sucesso!</h4>
                        <ul style="margin-top: 15px; padding-left: 20px;">
                            <?php foreach ($success as $msg): ?>
                                <li><?= htmlspecialchars($msg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-msg">
                        <h4>❌ Erros encontrados:</h4>
                        <ul style="margin-top: 15px; padding-left: 20px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (empty($errors) && $progress == 100): ?>
                    <div class="info-msg">
                        <h4>🎉 Próximos Passos:</h4>
                        <ul style="margin-top: 15px; padding-left: 20px;">
                            <li><strong>1.</strong> Delete este arquivo (install.php) por segurança</li>
                            <li><strong>2.</strong> Acesse seu site: <a href="./" target="_blank">Ver Portfolio</a></li>
                            <li><strong>3.</strong> Acesse o admin: <a href="./#admin" target="_blank">Painel Admin</a></li>
                            <li><strong>4.</strong> Senha admin: <code><?= htmlspecialchars($admin_pass) ?></code></li>
                            <li><strong>5.</strong> Adicione suas fotos e personalize o conteúdo</li>
                        </ul>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <a href="./" class="btn" style="display: inline-block; text-decoration: none; width: auto; margin-right: 15px;">
                            🚀 Ver Meu Portfolio
                        </a>
                        <a href="./#admin" class="btn" style="display: inline-block; text-decoration: none; width: auto; background: #27ae60;">
                            ⚙️ Painel Admin
                        </a>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Formulário de Instalação -->
                <form method="POST" id="installForm">
                    <div class="step active">
                        <h3><span class="step-icon">🗄️</span> Configuração do Banco de Dados</h3>
                        <div class="form-group">
                            <label>Host do Banco:</label>
                            <input type="text" name="db_host" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label>Nome do Banco:</label>
                            <input type="text" name="db_name" value="photographer_portfolio" required>
                        </div>
                        <div class="form-group">
                            <label>Usuário do Banco:</label>
                            <input type="text" name="db_user" value="root" required>
                        </div>
                        <div class="form-group">
                            <label>Senha do Banco:</label>
                            <input type="password" name="db_pass" placeholder="Deixe em branco se não tiver senha">
                        </div>
                    </div>

                    <div class="step">
                        <h3><span class="step-icon">🔐</span> Configuração de Segurança</h3>
                        <div class="form-group">
                            <label>Senha do Admin:</label>
                            <input type="password" name="admin_pass" value="admin123" required>
                            <small style="color: #7f8c8d; font-size: 0.9rem;">
                                ⚠️ Altere esta senha padrão por segurança
                            </small>
                        </div>
                    </div>

                    <div class="step">
                        <h3><span class="step-icon">🎨</span> Informações do Site</h3>
                        <div class="form-group">
                            <label>Nome do Site:</label>
                            <input type="text" name="site_name" value="Fotógrafo Portfólio" required>
                        </div>
                        <div class="form-group">
                            <label>Email de Contato:</label>
                            <input type="email" name="site_email" value="contato@seusite.com" required>
                        </div>
                    </div>

                    <div class="info-msg">
                        <h4>📋 Verificações Pré-Instalação:</h4>
                        <ul class="checklist">
                            <li>
                                <span class="check-icon <?= is_writable('.') ? 'success' : 'error' ?>">
                                    <?= is_writable('.') ? '✅' : '❌' ?>
                                </span>
                                Permissão de escrita na pasta raiz
                            </li>
                            <li>
                                <span class="check-icon <?= extension_loaded('pdo_mysql') ? 'success' : 'error' ?>">
                                    <?= extension_loaded('pdo_mysql') ? '✅' : '❌' ?>
                                </span>
                                Extensão PDO MySQL
                            </li>
                            <li>
                                <span class="check-icon <?= extension_loaded('gd') ? 'success' : 'error' ?>">
                                    <?= extension_loaded('gd') ? '✅' : '❌' ?>
                                </span>
                                Extensão GD (manipulação de imagens)
                            </li>
                            <li>
                                <span class="check-icon <?= file_exists('database/setup.sql') ? 'success' : 'error' ?>">
                                    <?= file_exists('database/setup.sql') ? '✅' : '❌' ?>
                                </span>
                                Arquivo SQL de instalação
                            </li>
                        </ul>
                    </div>

                    <button type="submit" class="btn" id="installBtn">
                        🚀 Instalar Portfolio de Fotógrafo
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>📸 Portfolio de Fotógrafo v2.0 | Desenvolvido com ❤️ para profissionais da fotografia</p>
        </div>
    </div>

    <script>
        document.getElementById('installForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('installBtn');
            btn.disabled = true;
            btn.innerHTML = '⏳ Instalando... Por favor aguarde';
            
            // Simular progresso
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 10;
                if (progress >= 90) {
                    clearInterval(interval);
                    progress = 90;
                }
                document.getElementById('progressBar').style.width = progress + '%';
            }, 200);
        });
    </script>
</body>
</html>
