<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>晴玖AI创作系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", sans-serif; background: #fff; }
        
        /* 导航栏 */
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 16px 48px; background: #fff; border-bottom: 1px solid #f0f0f0; }
        .navbar-left { display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); display: flex; align-items: center; justify-content: center; }
        .logo-icon span { color: #fff; font-size: 16px; }
        .logo-text { font-size: 18px; font-weight: 600; color: #333; }
        .navbar-center { display: flex; gap: 32px; }
        .navbar-center a { text-decoration: none; color: #666; font-size: 14px; transition: color 0.2s; }
        .navbar-center a:hover { color: #ff6b81; }
        .navbar-right { display: flex; gap: 12px; }
        .btn-login { padding: 8px 20px; border: 1px solid #ff6b81; border-radius: 20px; color: #ff6b81; background: #fff; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        .btn-login:hover { background: #fff5f7; }
        .btn-register { padding: 8px 20px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); border: none; border-radius: 20px; color: #fff; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        .btn-register:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255, 107, 129, 0.3); }
        
        /* 主要内容 */
        .main-content { padding: 64px 48px; text-align: center; }
        .section-title { font-size: 28px; font-weight: 600; color: #333; margin-bottom: 48px; }
        
        /* 特性卡片 */
        .features { display: flex; justify-content: center; gap: 64px; margin-bottom: 80px; }
        .feature-card { text-align: center; max-width: 280px; }
        .feature-icon { width: 72px; height: 72px; border-radius: 18px; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; }
        .feature-icon span { font-size: 28px; }
        .feature-icon.pink { background: linear-gradient(135deg, #fff5f7, #ffe4e8); }
        .feature-icon.blue { background: linear-gradient(135deg, #eff6ff, #dbeafe); }
        .feature-icon.green { background: linear-gradient(135deg, #f0fdf4, #dcfce7); }
        .feature-name { font-size: 16px; font-weight: 600; color: #333; margin-bottom: 8px; }
        .feature-desc { font-size: 13px; color: #999; line-height: 1.6; }
        
        /* CTA区域 */
        .cta-section { margin-bottom: 80px; }
        .cta-text { font-size: 14px; color: #999; margin-bottom: 24px; }
        .btn-start { padding: 14px 48px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); border: none; border-radius: 30px; color: #fff; cursor: pointer; font-size: 16px; font-weight: 500; transition: all 0.2s; }
        .btn-start:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255, 107, 129, 0.3); }
        
        /* 页脚 */
        .footer { background: #fafafa; padding: 32px 48px; text-align: center; }
        .footer-info { display: flex; justify-content: center; gap: 32px; margin-bottom: 16px; font-size: 13px; color: #999; }
        .footer-copyright { font-size: 12px; color: #ccc; }
        
        /* 登录弹窗 */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-overlay.show { display: flex; }
        .login-modal { background: #fff; border-radius: 20px; padding: 48px; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .modal-close { float: right; font-size: 24px; cursor: pointer; color: #999; }
        .modal-close:hover { color: #333; }
        .modal-title { font-size: 20px; font-weight: 600; color: #333; margin-bottom: 24px; }
        .alert { background: #fff5f5; color: #e53e3e; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: #333; margin-bottom: 8px; }
        .form-input { width: 100%; padding: 14px 16px; border: 2px solid #f0f0f0; border-radius: 12px; font-size: 15px; transition: border-color 0.2s; outline: none; }
        .form-input:focus { border-color: #ff6b81; }
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .modal-footer { text-align: center; margin-top: 20px; font-size: 14px; color: #999; }
        .modal-footer a { color: #ff6b81; text-decoration: none; }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="navbar-left">
            <div class="logo-icon"><span>🌞</span></div>
            <span class="logo-text">晴玖AI创作系统</span>
        </div>
        <div class="navbar-center">
            <a href="#home">首页</a>
            <a href="#features">功能介绍</a>
            <a href="gallery.php">观赏大厅</a>
        </div>
        <div class="navbar-right">
            <button class="btn-login" onclick="showLoginModal()">登录</button>
            <button class="btn-register" onclick="location.href='register.php'">注册</button>
        </div>
    </nav>
    
    <!-- 主要内容 -->
    <main class="main-content" id="home">
        <h2 class="section-title" id="features">为什么选择晴玖AI创作系统?</h2>
        
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon pink"><span>🧩</span></div>
                <div class="feature-name">多模型支持</div>
                <div class="feature-desc">集成多款顶级AI绘图模型，从快速草稿到专业级渲染，满足不同创作需求。</div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon blue"><span>⚡</span></div>
                <div class="feature-name">极速生成</div>
                <div class="feature-desc">强大的GPU集群保障，数秒内完成高清图片生成，让灵感即刻呈现。</div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon green"><span>🔒</span></div>
                <div class="feature-name">安全可靠</div>
                <div class="feature-desc">作品本地存储不丢失，完善的工单售后体系，使用更放心。</div>
            </div>
        </div>
        
        <div class="cta-section">
            <p class="cta-text">准备好开始你的AI创作之旅了吗?</p>
            <button class="btn-start" onclick="showLoginModal()">🚀 立即开始</button>
        </div>
    </main>
    
    <!-- 页脚 -->
    <footer class="footer">
        <div class="footer-info">
            <span>📞 3059530850</span>
            <span>💬 xiangyang@qq666</span>
            <span>📜 浙ICP备2025054489号-1</span>
        </div>
        <div class="footer-copyright">晴玖科技 © 2025</div>
    </footer>
    
    <!-- 登录弹窗 -->
    <div class="modal-overlay" id="loginModal">
        <div class="login-modal">
            <span class="modal-close" onclick="hideLoginModal()">&times;</span>
            <h3 class="modal-title">用户登录</h3>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="alert"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <form method="post" action="do_login.php">
                <div class="form-group">
                    <label class="form-label">用户名</label>
                    <input type="text" class="form-input" name="username" placeholder="请输入用户名" required>
                </div>
                <div class="form-group">
                    <label class="form-label">密码</label>
                    <input type="password" class="form-input" name="password" placeholder="请输入密码" required>
                </div>
                <button type="submit" class="btn-submit">登录</button>
            </form>
            
            <div class="modal-footer">
                还没有账号？<a href="register.php">立即注册</a>
            </div>
        </div>
    </div>
    
    <script>
        function showLoginModal() {
            document.getElementById('loginModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function hideLoginModal() {
            document.getElementById('loginModal').classList.remove('show');
            document.body.style.overflow = '';
        }
        
        document.getElementById('loginModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideLoginModal();
            }
        });
    </script>
</body>
</html>