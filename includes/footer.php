<?php
// Common footer for all pages
?>
    </div> <!-- Close container -->

    <footer class="bg-dark text-white pt-5 pb-3 mt-5">
        <div class="container">
            <div class="row">
                <!-- About -->
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold">AsekosiGo</h5>
                    <p class="small text-muted">Kenya's premier marketplace. Shop from hundreds of vendors in one place with ease and safety.</p>
                    <lottie-player 
                        src="https://assets5.lottiefiles.com/packages/lf20_touohxv0.json" 
                        background="transparent"  
                        speed="1"  
                        style="width: 80px; height: 80px;"  
                        loop  
                        autoplay>
                    </lottie-player>
                </div>

                <!-- Quick Links -->
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold">Quick Links</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="../index.php" class="text-white text-decoration-none footer-link">Home</a></li>
                        <li><a href="../customer/products.php" class="text-white text-decoration-none footer-link">Products</a></li>
                        <li><a href="../index.php?view=register&type=vendor" class="text-white text-decoration-none footer-link">Become a Vendor</a></li>
                        <li><a href="#" class="text-white text-decoration-none footer-link">Contact Us</a></li>
                        <li><a href="#" class="text-white text-decoration-none footer-link">FAQs</a></li>
                    </ul>
                </div>

                <!-- Social & Contact -->
                <div class="col-md-4 mb-4 text-md-end">
                    <h5 class="fw-bold">Connect</h5>
                    <div class="d-flex justify-content-md-end gap-3 mb-2">
                        <a href="#" class="text-white fs-5 footer-icon"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white fs-5 footer-icon"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white fs-5 footer-icon"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white fs-5 footer-icon"><i class="bi bi-whatsapp"></i></a>
                    </div>
                    <p class="small text-muted mb-0">Email: support@asekosigo.com<br>Phone: +254 700 123 456</p>
                </div>
            </div>

            <hr class="border-secondary my-3">

            <!-- Copyright -->
            <div class="text-center small text-muted">
                &copy; <?php echo date('Y'); ?> AsekosiGo. All rights reserved.
            </div>
        </div>
    </footer>

    <style>
        /* Footer Hover Effects */
        .footer-link:hover {
            color: #ffc107 !important;
            text-decoration: underline;
        }
        .footer-icon:hover {
            color: #ffc107 !important;
            transform: scale(1.2);
            transition: transform 0.2s, color 0.2s;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>
