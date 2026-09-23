    <?php
    $pagina_atual = basename($_SERVER["PHP_SELF"]);
    ?>


    <aside class="sidebar">

        <div class="logo">
            K<span class="logo-mark">o</span>plo
        </div>


        <nav class="menu">

            <div class="menu-title">
                Menu
            </div>


            <!-- Dashboard -->

            <a href="dashboard.php"  class="<?php echo $pagina_atual == 'dashboard.php' ? 'active' : ''; ?>">

                <svg
                    class="nav-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                </svg>

                <span>Dashboard</span>

            </a>


            <!-- Transações -->

            <a href="transacoes.php"  class="<?php echo $pagina_atual == 'transacoes.php' ? 'active' : ''; ?>">

                <svg
                    class="nav-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M4 7h16"></path>
                    <path d="M4 12h16"></path>
                    <path d="M4 17h10"></path>
                    <circle cx="18" cy="17" r="2"></circle>
                </svg>

                <span>Transações</span>

            </a>


            <!-- Nova transação -->

            <a href="transacao.php" class="<?php echo $pagina_atual == 'transacao.php' ? 'active' : ''; ?>">

                <svg
                    class="nav-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 8v8"></path>
                    <path d="M8 12h8"></path>
                </svg>

                <span>Nova transação</span>

            </a>


            <!-- Metas -->

            <a href="metas.php"  class="<?php echo $pagina_atual == 'metas.php' ? 'active' : ''; ?>">

                <svg
                    class="nav-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8"></circle>
                    <circle cx="12" cy="12" r="4"></circle>
                    <path d="M12 4V2"></path>
                    <path d="M20 12h2"></path>
                </svg>

                <span>Metas</span>

            </a>


            <!-- IA -->

            <a href="analise_ia.php"  class="<?php echo $pagina_atual == 'analise_ia.php' ? 'active' : ''; ?>">

                <svg
                    class="nav-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="5" y="5" width="14" height="14" rx="3"></rect>
                    <path d="M9 9h6v6H9z"></path>
                    <path d="M9 2v3"></path>
                    <path d="M15 2v3"></path>
                    <path d="M9 19v3"></path>
                    <path d="M15 19v3"></path>
                    <path d="M2 9h3"></path>
                    <path d="M2 15h3"></path>
                    <path d="M19 9h3"></path>
                    <path d="M19 15h3"></path>
                </svg>

                <span>Koplo IA</span>

            </a>


            <!-- Configurações -->

            <a href="configuracoes.php">

                <svg
                    class="nav-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 1.7-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V22h-2.4v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1-1.7-1.7.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H5.2v-2.4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1 1.7-1.7.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5V5h2.4v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 1.7 1.7-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.2v2.4h-.2a1.7 1.7 0 0 0-1.5 1z"></path>
                </svg>

                <span>Configurações</span>

            </a>

        </nav>


        <!-- DICA -->

        <div class="sidebar-bottom">

            <div class="sidebar-tip">

                <div class="sidebar-tip-title">
                    Dica Koplo
                </div>

                <div class="sidebar-tip-text">
                    Acompanhe seus gastos e mantenha suas finanças organizadas.
                </div>

                <a href="analise_ia.php" class="sidebar-tip-link">
                    Saiba mais
                </a>

            </div>

        </div>

    </aside>