<footer class="admin-footer">
    <div class="container">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Blog Admin Panel. All rights reserved.</p>
            <div class="footer-links">
                <a href="#"><i class="fas fa-question-circle"></i> Help</a>
                <a href="#"><i class="fas fa-shield-alt"></i> Privacy Policy</a>
                <a href="#"><i class="fas fa-file-alt"></i> Terms</a>
            </div>
        </div>
    </div>
</footer>

<style>
    .admin-footer {
        background-color: #2c3e50;
        color: white;
        padding: 15px 0;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        z-index: 1000;
    }

    .content {
        margin-bottom: 60px; /* Add space at the bottom so content isn't hidden behind footer */
    }

    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .admin-footer p {
        margin: 0;
        font-size: 0.9rem;
    }

    .footer-links {
        display: flex;
        gap: 15px;
    }

    .footer-links a {
        color: white;
        text-decoration: none;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s ease;
    }

    .footer-links a:hover {
        color: #1cc88a;
    }

    .footer-links a i {
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }

        .footer-links {
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .admin-footer {
            padding: 10px 0;
        }
    }
</style>