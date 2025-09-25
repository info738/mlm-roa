<div class="rpp-dashboard-container">
    <!-- Header -->
    <div class="rpp-dashboard-header">
        <div class="rpp-header-left">
            <h1>Partnerský dashboard</h1>
            <p class="rpp-welcome-text">Vítejte zpět, <strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong></p>
            
            <?php 
            $groups_class = new RPP_Partner_Groups();
            $partner_group = $groups_class->get_group($partner->group_id ?? 1);
            if ($partner_group && $partner_group->volume_based): 
                // Calculate next restart date
                $restart_period = $partner_group->restart_period ?? 'monthly';
                $next_restart = '';
                switch ($restart_period) {
                    case 'weekly':
                        $next_restart = date('j.n.Y', strtotime('next monday'));
                        break;
                    case 'monthly':
                        $next_restart = date('j.n.Y', strtotime('first day of next month'));
                        break;
                    case 'quarterly':
                        $current_quarter = ceil(date('n') / 3);
                        $next_quarter_month = ($current_quarter * 3) + 1;
                        if ($next_quarter_month > 12) {
                            $next_quarter_month = 1;
                            $year = date('Y') + 1;
                        } else {
                            $year = date('Y');
                        }
                        $next_restart = date('j.n.Y', mktime(0, 0, 0, $next_quarter_month, 1, $year));
                        break;
                    case 'semi-annually':
                        $current_month = date('n');
                        $next_restart_month = $current_month <= 6 ? 7 : 1;
                        $year = $next_restart_month == 1 ? date('Y') + 1 : date('Y');
                        $next_restart = date('j.n.Y', mktime(0, 0, 0, $next_restart_month, 1, $year));
                        break;
                    case 'annually':
                        $next_restart = date('j.n.Y', mktime(0, 0, 0, 1, 1, date('Y') + 1));
                        break;
                }
                
                // Calculate current team volume for the period
                $current_volume = $this->calculate_team_volume_for_period($partner->id, $restart_period);
            ?>
            <div class="rpp-group-info">
                <span class="rpp-group-badge volume-based">
                    📊 <?php echo esc_html($partner_group->name); ?> 
                    (<?php echo $partner_group->volume_percentage; ?>% z obratu týmu)
                </span>
                <?php if ($next_restart): ?>
                <div class="rpp-restart-countdown">
                    <small>Restart bonusů: <?php echo $next_restart; ?></small>
                </div>
                <?php endif; ?>
            </div>
            
            <?php 
            // Show bonus progress if there are bonus thresholds
            if (!empty($partner_group->bonus_thresholds)): ?>
            <div class="rpp-bonus-progress">
                <h4>🎯 Bonusy za výkon</h4>
                <?php foreach ($partner_group->bonus_thresholds as $bonus): 
                    $bonus_restart = $bonus['restart'] ?? 'monthly';
                    $bonus_volume = $this->calculate_team_volume_for_period($partner->id, $bonus_restart);
                    $progress_percent = min(100, ($bonus_volume / $bonus['turnover']) * 100);
                    
                    // Calculate next restart for this specific bonus
                    $bonus_next_restart = '';
                    switch ($bonus_restart) {
                        case 'weekly':
                            $bonus_next_restart = date('j.n.Y', strtotime('next monday'));
                            break;
                        case 'monthly':
                            $bonus_next_restart = date('j.n.Y', strtotime('first day of next month'));
                            break;
                        case 'quarterly':
                            $current_quarter = ceil(date('n') / 3);
                            $next_quarter_month = ($current_quarter * 3) + 1;
                            if ($next_quarter_month > 12) {
                                $next_quarter_month = 1;
                                $year = date('Y') + 1;
                            } else {
                                $year = date('Y');
                            }
                            $bonus_next_restart = date('j.n.Y', mktime(0, 0, 0, $next_quarter_month, 1, $year));
                            break;
                        case 'semi-annually':
                            $current_month = date('n');
                            $next_restart_month = $current_month <= 6 ? 7 : 1;
                            $year = $next_restart_month == 1 ? date('Y') + 1 : date('Y');
                            $bonus_next_restart = date('j.n.Y', mktime(0, 0, 0, $next_restart_month, 1, $year));
                            break;
                        case 'annually':
                            $bonus_next_restart = date('j.n.Y', mktime(0, 0, 0, 1, 1, date('Y') + 1));
                            break;
                    }
                ?>
                <div class="rpp-bonus-item">
                    <div class="rpp-bonus-header">
                        <span class="rpp-bonus-target">
                            🎯 <?php echo number_format($bonus['turnover'], 0, ',', ' '); ?> Kč
                        </span>
                        <span class="rpp-bonus-reward">
                            💰 <?php echo number_format($bonus['amount'], 0, ',', ' '); ?> Kč
                        </span>
                    </div>
                    <div class="rpp-progress-bar">
                        <div class="rpp-progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                        <div class="rpp-progress-text">
                            <?php echo number_format($bonus_volume, 0, ',', ' '); ?> / <?php echo number_format($bonus['turnover'], 0, ',', ' '); ?> Kč
                            (<?php echo round($progress_percent, 1); ?>%)
                        </div>
                    </div>
                    <div class="rpp-bonus-restart">
                        <small>
                            <?php 
                            $restart_labels = [
                                'weekly' => 'Týdenní restart',
                                'monthly' => 'Měsíční restart', 
                                'quarterly' => 'Kvartální restart',
                                'semi-annually' => 'Půlroční restart',
                                'annually' => 'Roční restart'
                            ];
                            echo $restart_labels[$bonus_restart] ?? 'Restart';
                            ?>: <?php echo $bonus_next_restart; ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="rpp-header-right">
            <div class="rpp-partner-code-display">
                <span class="rpp-code-label">Váš kód:</span>
                <span class="rpp-code-value"><?php echo esc_html($partner->partner_code); ?></span>
            </div>
            <div class="rpp-user-menu">
                <button class="rpp-user-toggle" id="user-menu-toggle">
                    <span class="dashicons dashicons-admin-users"></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
                <div class="rpp-user-dropdown" id="user-dropdown">
                    <a href="#" class="rpp-dropdown-item" id="change-password">
                        <span class="dashicons dashicons-lock"></span>
                        Změnit heslo
                    </a>
                    <a href="<?php echo wp_logout_url(); ?>" class="rpp-dropdown-item">
                        <span class="dashicons dashicons-exit"></span>
                        Odhlásit se
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Overview -->
    <div class="rpp-stats-overview">
        <div class="rpp-stat-card">
            <div class="rpp-stat-icon">💰</div>
            <div class="rpp-stat-content">
                <div class="rpp-stat-value"><?php echo wc_price($stats['total_commissions'] ?? 0); ?></div>
                <div class="rpp-stat-label">Celkové výdělky</div>
            </div>
        </div>
        <div class="rpp-stat-card">
            <div class="rpp-stat-icon">✅</div>
            <div class="rpp-stat-content">
                <div class="rpp-stat-value"><?php echo wc_price($stats['paid_commissions'] ?? 0); ?></div>
                <div class="rpp-stat-label">Vyplaceno</div>
            </div>
        </div>
        <div class="rpp-stat-card">
            <div class="rpp-stat-icon">⏳</div>
            <div class="rpp-stat-content">
                <div class="rpp-stat-value"><?php echo wc_price($stats['pending_commissions'] ?? 0); ?></div>
                <div class="rpp-stat-label">K výplatě</div>
            </div>
        </div>
        <div class="rpp-stat-card">
            <div class="rpp-stat-icon">👥</div>
            <div class="rpp-stat-content">
                <div class="rpp-stat-value"><?php echo intval($team_stats['direct_referrals'] ?? 0); ?></div>
                <div class="rpp-stat-label">Přímí referálové</div>
            </div>
        </div>
        <div class="rpp-stat-card">
            <div class="rpp-stat-icon">🎯</div>
            <div class="rpp-stat-content">
                <div class="rpp-stat-value"><?php echo $tracking_stats['conversion_rate'] ?? 0; ?>%</div>
                <div class="rpp-stat-label">Konverzní poměr</div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <!-- Navigation Tabs -->
    <div class="rpp-tabs-container">
        <div class="rpp-tabs-nav">
            <button class="rpp-tab-btn active" data-tab="overview">📊 Přehled</button>
            <button class="rpp-tab-btn" data-tab="tracking">🔍 Tracking</button>
            <button class="rpp-tab-btn" data-tab="team">👥 Můj tým</button>
            <button class="rpp-tab-btn" data-tab="orders">🛒 Objednávky</button>
            <button class="rpp-tab-btn" data-tab="payouts">💰 Výplaty</button>
        </div>
        
        <!-- Tab Content -->
        <div class="rpp-tab-content active" id="tab-overview">
            <div class="rpp-main-content">
                <!-- Left Column -->
                <div class="rpp-left-column">
                    <!-- Bank Account Settings -->
                    <div class="rpp-card">
                        <div class="rpp-card-header">
                            <h3>🏦 Nastavení účtu</h3>
                        </div>
                        <div class="rpp-card-body">
                            <?php 
                            $bank_account = get_user_meta(get_current_user_id(), 'rpp_bank_account', true);
                            $bank_name = get_user_meta(get_current_user_id(), 'rpp_bank_name', true);
                            ?>
                            
                            <?php if ($bank_account): ?>
                                <div class="rpp-bank-info">
                                    <div class="rpp-bank-detail">
                                        <strong>Číslo účtu:</strong> <?php echo esc_html($bank_account); ?>
                                    </div>
                                    <?php if ($bank_name): ?>
                                    <div class="rpp-bank-detail">
                                        <strong>Banka:</strong> <?php echo esc_html($bank_name); ?>
                                    </div>
                                    <?php endif; ?>
                                    <button class="rpp-btn rpp-btn-secondary rpp-btn-small" id="edit-bank-account">
                                        <span class="dashicons dashicons-edit"></span>
                                        Upravit
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="rpp-no-bank-account">
                                    <p>⚠️ Nemáte nastavené číslo účtu pro výplaty.</p>
                                    <button class="rpp-btn rpp-btn-primary" id="add-bank-account">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        Přidat číslo účtu
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Referral Tools -->
                    <div class="rpp-card">
                        <div class="rpp-card-header">
                            <h3>🔗 Referenční nástroje</h3>
                        </div>
                        <div class="rpp-card-body">
                            <div class="rpp-referral-link">
                                <label>Váš hlavní referenční odkaz:</label>
                                <div class="rpp-link-container">
                                    <input type="text" id="referral-link" value="<?php echo esc_url($referral_link); ?>" readonly>
                                    <button type="button" class="rpp-btn rpp-btn-primary" onclick="copyReferralLink()">
                                        <span class="dashicons dashicons-admin-page"></span>
                                        Kopírovat
                                    </button>
                                </div>
                            </div>
                            
                            <div class="rpp-custom-link">
                                <label>Generátor vlastního odkazu:</label>
                                <div class="rpp-link-container">
                                    <input type="url" id="custom-page" placeholder="Zadejte URL stránky">
                                    <button type="button" class="rpp-btn rpp-btn-secondary" onclick="generateCustomLink()">
                                        Generovat
                                    </button>
                                </div>
                            </div>
                            
                            <div class="rpp-social-share">
                                <label>Sdílení na sociálních sítích:</label>
                                <div class="rpp-social-buttons">
                                    <button class="rpp-social-btn facebook" onclick="shareOnSocial('facebook')">
                                        <span class="dashicons dashicons-facebook"></span>
                                        Facebook
                                    </button>
                                    <button class="rpp-social-btn twitter" onclick="shareOnSocial('twitter')">
                                        <span class="dashicons dashicons-twitter"></span>
                                        Twitter
                                    </button>
                                    <button class="rpp-social-btn email" onclick="shareOnSocial('email')">
                                        <span class="dashicons dashicons-email"></span>
                                        Email
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Performance -->
                    <div class="rpp-card">
                        <div class="rpp-card-header">
                            <h3>📊 Výkonnost</h3>
                        </div>
                        <div class="rpp-card-body">
                            <div class="rpp-performance-grid">
                                <div class="rpp-performance-item">
                                    <div class="rpp-performance-value"><?php echo intval($tracking_stats['clicks'] ?? 0); ?></div>
                                    <div class="rpp-performance-label">Celkové kliky</div>
                                </div>
                                <div class="rpp-performance-item">
                                    <div class="rpp-performance-value"><?php echo intval($tracking_stats['conversions'] ?? 0); ?></div>
                                    <div class="rpp-performance-label">Konverze</div>
                                </div>
                                <div class="rpp-performance-item">
                                    <div class="rpp-performance-value"><?php echo esc_html($partner->commission_rate); ?>%</div>
                                    <div class="rpp-performance-label">Provizní sazba</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="rpp-right-column">
                    <!-- Quick Stats -->
                    <div class="rpp-card">
                        <div class="rpp-card-header">
                            <h3>⚡ Rychlé statistiky</h3>
                        </div>
                        <div class="rpp-card-body">
                            <div class="rpp-quick-stats">
                                <div class="rpp-quick-stat">
                                    <div class="rpp-quick-number"><?php echo intval($tracking_stats['clicks'] ?? 0); ?></div>
                                    <div class="rpp-quick-label">Celkové kliky</div>
                                </div>
                                <div class="rpp-quick-stat">
                                    <div class="rpp-quick-number"><?php echo intval($tracking_stats['conversions'] ?? 0); ?></div>
                                    <div class="rpp-quick-label">Konverze</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="rpp-tab-content" id="tab-tracking">
            <div class="rpp-card">
                <div class="rpp-card-header">
                    <h3>🔍 Tracking návštěvníků</h3>
                    <button class="rpp-btn rpp-btn-secondary rpp-btn-small" id="refresh-tracking">
                        <span class="dashicons dashicons-update"></span>
                        Obnovit
                    </button>
                </div>
                <div class="rpp-card-body">
                    <div id="tracking-content">
                        <div style="text-align: center; padding: 40px;">
                            <span class="spinner is-active"></span>
                            Načítám tracking data...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="rpp-tab-content" id="tab-team">
            <div class="rpp-card">
                <div class="rpp-card-header">
                    <h3>👥 Struktura týmu</h3>
                    <button class="rpp-btn rpp-btn-secondary rpp-btn-small" id="refresh-team">
                        <span class="dashicons dashicons-update"></span>
                        Obnovit
                    </button>
                </div>
                <div class="rpp-card-body">
                    <div id="team-content">
                        <div style="text-align: center; padding: 40px;">
                            <span class="spinner is-active"></span>
                            Načítám data týmu...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="rpp-tab-content" id="tab-orders">
            <div class="rpp-card">
                <div class="rpp-card-header">
                    <h3>🛒 Objednávky a provize</h3>
                    <button class="rpp-btn rpp-btn-secondary rpp-btn-small" id="refresh-orders">
                        <span class="dashicons dashicons-update"></span>
                        Obnovit
                    </button>
                </div>
                <div class="rpp-card-body">
                    <div id="orders-content">
                        <div style="text-align: center; padding: 40px;">
                            <span class="spinner is-active"></span>
                            Načítám objednávky...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="rpp-tab-content" id="tab-payouts">
            <div class="rpp-card">
                <div class="rpp-card-header">
                    <h3>💰 Výplaty</h3>
                    <button class="rpp-btn rpp-btn-secondary rpp-btn-small" id="refresh-payouts">
                        <span class="dashicons dashicons-update"></span>
                        Obnovit
                    </button>
                </div>
                <div class="rpp-card-body">
                    <div id="payouts-content">
                        <div style="text-align: center; padding: 40px;">
                            <span class="spinner is-active"></span>
                            Načítám data výplat...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
</div>

<!-- Modal pro nastavení čísla účtu -->
<div id="bank-account-modal" class="rpp-modal" style="display: none;">
    <div class="rpp-modal-overlay"></div>
    <div class="rpp-modal-content">
        <div class="rpp-modal-header">
            <h3>🏦 Nastavení čísla účtu</h3>
            <button class="rpp-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <form id="bank-account-form">
            <div class="rpp-modal-body">
                <div class="rpp-form-group">
                    <label for="bank-account">Číslo účtu *</label>
                    <input type="text" id="bank-account" name="bank_account" 
                           value="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'rpp_bank_account', true)); ?>"
                           placeholder="123456789/0100" required pattern="\d+/\d{4}">
                    <small>Formát: číslo_účtu/kód_banky (např. 123456789/0100)</small>
                </div>
                <div class="rpp-form-group">
                    <label for="bank-name">Název banky</label>
                    <input type="text" id="bank-name" name="bank_name" 
                           value="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'rpp_bank_name', true)); ?>"
                           placeholder="Komerční banka">
                </div>
            </div>
            <div class="rpp-modal-footer">
                <button type="button" class="rpp-btn rpp-btn-secondary" onclick="closeBankAccountModal()">Zrušit</button>
                <button type="submit" class="rpp-btn rpp-btn-primary">Uložit účet</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pro změnu hesla -->
<div id="password-modal" class="rpp-modal" style="display: none;">
    <div class="rpp-modal-overlay"></div>
    <div class="rpp-modal-content">
        <div class="rpp-modal-header">
            <h3>Změna hesla</h3>
            <button class="rpp-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <form id="change-password-form">
            <div class="rpp-modal-body">
                <div class="rpp-form-group">
                    <label for="current-password">Současné heslo:</label>
                    <input type="password" id="current-password" name="current_password" required>
                </div>
                <div class="rpp-form-group">
                    <label for="new-password">Nové heslo:</label>
                    <input type="password" id="new-password" name="new_password" required>
                </div>
                <div class="rpp-form-group">
                    <label for="confirm-password">Potvrdit nové heslo:</label>
                    <input type="password" id="confirm-password" name="confirm_password" required>
                </div>
            </div>
            <div class="rpp-modal-footer">
                <button type="button" class="rpp-btn rpp-btn-secondary" onclick="closePasswordModal()">Zrušit</button>
                <button type="submit" class="rpp-btn rpp-btn-primary">Změnit heslo</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Business Dashboard Styles */
.rpp-dashboard-container {
    width: 100%;
    max-width: 1400px;
    min-width: 1100px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    box-sizing: border-box;
}

.rpp-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    border-left: 4px solid #2d5a27;
}

.rpp-header-left h1 {
    margin: 0 0 5px 0;
    color: #2d5a27;
    font-size: 28px;
    font-weight: 600;
}

.rpp-welcome-text {
    margin: 0;
    color: #666;
    font-size: 16px;
}

.rpp-group-info {
    margin-top: 10px;
}

.rpp-group-badge.volume-based {
    background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
    color: #2d5a27;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.rpp-restart-countdown {
    margin-top: 4px;
}

.rpp-restart-countdown small {
    color: #666;
    font-size: 11px;
}

.rpp-bonus-progress {
    margin-top: 15px;
    padding: 15px;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #d4af37;
}

.rpp-bonus-progress h4 {
    margin: 0 0 15px 0;
    color: #2d5a27;
    font-size: 16px;
}

.rpp-bonus-item {
    margin-bottom: 15px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 6px;
}

.rpp-bonus-item:last-child {
    margin-bottom: 0;
}

.rpp-bonus-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.rpp-bonus-target {
    font-weight: 600;
    color: #2d5a27;
}

.rpp-bonus-reward {
    font-weight: 600;
    color: #d4af37;
}

.rpp-progress-bar {
    position: relative;
    height: 24px;
    background: #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 6px;
}

.rpp-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #d4af37, #f4d03f);
    transition: width 0.3s ease;
}

.rpp-progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 11px;
    font-weight: 600;
    color: #2d5a27;
}

.rpp-bonus-restart {
    text-align: right;
}

.rpp-bonus-restart small {
    color: #666;
    font-size: 10px;
}

.rpp-header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.rpp-partner-code-display {
    text-align: right;
}

.rpp-code-label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.rpp-code-value {
    font-family: monospace;
    background: #f0f0f0;
    padding: 6px 12px;
    border-radius: 4px;
    font-weight: 600;
    color: #2d5a27;
}

.rpp-user-menu {
    position: relative;
}

.rpp-user-toggle {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
}

.rpp-user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    min-width: 180px;
    z-index: 1000;
    display: none;
    margin-top: 5px;
}

.rpp-user-dropdown.show {
    display: block;
}

.rpp-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    color: #333;
    text-decoration: none;
    transition: background 0.3s;
}

.rpp-dropdown-item:hover {
    background: #f8f9fa;
}

.rpp-stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.rpp-stat-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.2s;
}

.rpp-stat-card:hover {
    transform: translateY(-2px);
}

.rpp-stat-icon {
    font-size: 32px;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 8px;
}

.rpp-stat-content {
    flex: 1;
}

.rpp-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #2d5a27;
    margin-bottom: 4px;
}

.rpp-stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
}

.rpp-main-content {
    width: 100%;
    display: flex;
    gap: 30px;
}

.rpp-left-column,
.rpp-right-column {
    flex: 1;
    min-width: 0;
}

.rpp-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    width: 100%;
    box-sizing: border-box;
}

.rpp-card-header {
    padding: 20px 25px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rpp-card-header h3 {
    margin: 0;
    color: #2d5a27;
    font-size: 18px;
    font-weight: 600;
}

.rpp-card-body {
    padding: 25px;
}

.rpp-referral-link,
.rpp-custom-link,
.rpp-social-share {
    margin-bottom: 25px;
}

.rpp-referral-link:last-child,
.rpp-custom-link:last-child,
.rpp-social-share:last-child {
    margin-bottom: 0;
}

.rpp-referral-link label,
.rpp-custom-link label,
.rpp-social-share label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.rpp-link-container {
    display: flex;
    gap: 10px;
}

.rpp-link-container input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.rpp-btn {
    display: inline-flex;
    align-items: center;
    padding: 10px 16px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
    gap: 6px;
}

.rpp-btn-primary {
    background: #2d5a27;
    color: white;
}

.rpp-btn-primary:hover {
    background: #1a3d1a;
}

.rpp-btn-secondary {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.rpp-btn-secondary:hover {
    background: #e9ecef;
}

.rpp-btn-small {
    padding: 6px 12px;
    font-size: 12px;
}

.rpp-social-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.rpp-social-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    color: white;
    transition: opacity 0.3s;
}

.rpp-social-btn:hover {
    opacity: 0.9;
}

.rpp-social-btn.facebook {
    background: #1877f2;
}

.rpp-social-btn.twitter {
    background: #1da1f2;
}

.rpp-social-btn.email {
    background: #666;
}

.rpp-performance-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.rpp-performance-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.rpp-performance-value {
    font-size: 20px;
    font-weight: 700;
    color: #2d5a27;
    margin-bottom: 4px;
}

.rpp-performance-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
}

.rpp-team-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.rpp-team-stat {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.rpp-team-number {
    font-size: 18px;
    font-weight: 700;
    color: #2d5a27;
    margin-bottom: 4px;
}

.rpp-team-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
}

.rpp-direct-referrals h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
}

.rpp-referrals-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rpp-referral-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.rpp-referral-name {
    font-weight: 600;
    color: #333;
}

.rpp-referral-earnings {
    font-weight: 600;
    color: #2d5a27;
    font-size: 12px;
}

.rpp-commissions-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rpp-orders-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rpp-order-item {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    border-left: 3px solid #2d5a27;
}

.rpp-order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.rpp-order-date {
    font-size: 12px;
    color: #666;
    font-weight: 600;
}

.rpp-order-number {
    font-family: monospace;
    background: #2d5a27;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.rpp-order-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.rpp-order-value {
    font-size: 14px;
    color: #333;
    font-weight: 600;
}

.rpp-commission-amount {
    font-weight: 600;
    color: #2d5a27;
}

.rpp-referrers-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rpp-referrer-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.rpp-referrer-url {
    font-size: 12px;
    color: #333;
    flex: 1;
    margin-right: 10px;
    word-break: break-all;
}

.rpp-referrer-count {
    font-weight: 600;
    color: #2d5a27;
    font-size: 11px;
    background: white;
    padding: 2px 8px;
    border-radius: 12px;
}

.rpp-commission-status {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    align-self: flex-start;
}

.rpp-status-pending { background: #fff3cd; color: #856404; }
.rpp-status-approved { background: #d1ecf1; color: #0c5460; }
.rpp-status-paid { background: #d4edda; color: #155724; }
.rpp-status-requested { background: #fff3cd; color: #856404; }
.rpp-status-completed { background: #d4edda; color: #155724; }
.rpp-status-rejected { background: #f8d7da; color: #721c24; }

.rpp-empty-state {
    text-align: center;
    padding: 30px;
    color: #666;
}

/* Modal Styles */
.rpp-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rpp-modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.rpp-modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rpp-modal-header h3 {
    margin: 0;
    color: #2d5a27;
    font-size: 18px;
}

.rpp-modal-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #666;
}

.rpp-modal-body {
    padding: 25px;
}

.rpp-modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #f0f0f0;
    text-align: right;
}

.rpp-modal-footer .rpp-btn {
    margin-left: 10px;
}

.rpp-form-group {
    margin-bottom: 20px;
}

.rpp-form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #333;
}

.rpp-form-group input,
.rpp-form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.rpp-form-group small {
    display: block;
    margin-top: 4px;
    color: #666;
    font-size: 12px;
}

/* Tabs Styles */
.rpp-tabs-container {
    margin-bottom: 30px;
    width: 100%;
}

.rpp-tabs-nav {
    display: flex;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    overflow: hidden;
    width: 100%;
}

.rpp-tab-btn {
    flex: 1;
    padding: 15px 20px;
    border: none;
    background: white;
    color: #666;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border-right: 1px solid #f0f0f0;
}

.rpp-tab-btn:last-child {
    border-right: none;
}

.rpp-tab-btn:hover {
    background: #f8f9fa;
    color: #2d5a27;
}

.rpp-tab-btn.active {
    background: #2d5a27;
    color: white;
}

.rpp-tab-content {
    display: none;
    width: 100%;
}

.rpp-tab-content.active {
    display: block;
    width: 100%;
}

.rpp-quick-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.rpp-quick-stat {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.rpp-quick-number {
    font-size: 20px;
    font-weight: 700;
    color: #2d5a27;
    margin-bottom: 4px;
}

.rpp-quick-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
}

/* Bank Account Styles */
.rpp-bank-info {
    background: #e8f5e8;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid #4a7c59;
}

.rpp-bank-detail {
    margin-bottom: 8px;
    color: #2d5a27;
}

.rpp-no-bank-account {
    text-align: center;
    padding: 20px;
    background: #fff3cd;
    border-radius: 8px;
    border-left: 4px solid #ffc107;
}

.rpp-no-bank-account p {
    margin: 0 0 16px 0;
    color: #856404;
    font-weight: 600;
}

/* Tracking Styles */
.rpp-tracking-list {
    max-height: 500px;
    overflow-y: auto;
}

.rpp-tracking-item {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 12px;
    border-left: 3px solid #2d5a27;
}

.rpp-tracking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.rpp-tracking-date {
    font-size: 12px;
    color: #666;
    font-weight: 600;
}

.rpp-tracking-type {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.rpp-type-click { background: #e3f2fd; color: #1976d2; }
.rpp-type-sale { background: #e8f5e8; color: #2d5a27; }
.rpp-type-conversion { background: #fff3e0; color: #f57c00; }

.rpp-tracking-details {
    font-size: 12px;
    color: #555;
}

.rpp-tracking-details div {
    margin-bottom: 4px;
}

/* Team Table Styles */
.rpp-team-table,
.rpp-orders-table,
.rpp-payout-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.rpp-team-table th,
.rpp-team-table td,
.rpp-orders-table th,
.rpp-orders-table td,
.rpp-payout-table th,
.rpp-payout-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.rpp-team-table th,
.rpp-orders-table th,
.rpp-payout-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2d5a27;
    font-size: 12px;
    text-transform: uppercase;
}

.rpp-team-table tr:hover,
.rpp-orders-table tr:hover,
.rpp-payout-table tr:hover {
    background: #f8f9fa;
}

/* Payout specific styles */
.rpp-payout-form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #4a7c59;
}

.rpp-payout-form-section h4 {
    margin: 0 0 16px 0;
    color: #2d5a27;
    font-size: 16px;
}

.rpp-available-amount {
    background: white;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    text-align: center;
}

.rpp-amount-large {
    font-size: 24px;
    font-weight: 700;
    color: #d4af37;
    margin-bottom: 4px;
}

.rpp-amount-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
}

.rpp-progress-container {
    margin: 12px 0;
}

.rpp-progress-bar {
    background: #e8f5e8;
    border-radius: 10px;
    height: 8px;
    overflow: hidden;
    margin-bottom: 8px;
}

.rpp-progress-fill {
    background: linear-gradient(90deg, #4a7c59, #66bb6a);
    height: 100%;
    transition: width 0.3s ease;
}

.rpp-progress-text {
    font-size: 11px;
    color: #666;
    text-align: center;
}

.rpp-info-box {
    background: #e3f2fd;
    border: 1px solid #bbdefb;
    border-radius: 8px;
    padding: 16px;
    color: #1976d2;
    margin-bottom: 16px;
}

.rpp-warning-box {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 16px;
    color: #856404;
    margin-bottom: 16px;
}

.rpp-file-input {
    width: 100%;
    padding: 12px;
    border: 2px dashed #e8f5e8;
    border-radius: 8px;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s ease;
}

.rpp-file-input:hover {
    border-color: #4a7c59;
    background: #e8f5e8;
}

.rpp-invoice-link {
    color: #2d5a27;
    text-decoration: none;
    font-weight: 600;
    padding: 4px 8px;
    background: #e8f5e8;
    border-radius: 4px;
    font-size: 12px;
    transition: all 0.3s ease;
}

.rpp-invoice-link:hover {
    background: #d4edda;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .rpp-dashboard-container {
        min-width: auto;
        width: 100%;
        padding: 10px;
    }
    
    .rpp-dashboard-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .rpp-stats-overview {
        grid-template-columns: 1fr;
    }
    
    .rpp-main-content {
        flex-direction: column;
    }
    
    .rpp-link-container {
        flex-direction: column;
    }
    
    .rpp-social-buttons {
        justify-content: center;
    }
    
    .rpp-performance-grid,
    .rpp-team-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabBtns = document.querySelectorAll('.rpp-tab-btn');
    const tabContents = document.querySelectorAll('.rpp-tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            
            // Remove active class from all tabs and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
            
            // Load content for specific tabs
            if (tabId === 'tracking' && !document.getElementById('tracking-content').dataset.loaded) {
                loadTrackingData();
            } else if (tabId === 'team' && !document.getElementById('team-content').dataset.loaded) {
                loadTeamData();
            } else if (tabId === 'orders' && !document.getElementById('orders-content').dataset.loaded) {
                loadOrdersData();
            } else if (tabId === 'payouts' && !document.getElementById('payouts-content').dataset.loaded) {
                loadPayoutsData();
            }
        });
    });
    
    // User menu toggle
    const userMenuToggle = document.getElementById('user-menu-toggle');
    const userDropdown = document.getElementById('user-dropdown');
    
    if (userMenuToggle && userDropdown) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            userDropdown.classList.remove('show');
        });
    }
    
    // Change password modal
    const changePasswordBtn = document.getElementById('change-password');
    const passwordModal = document.getElementById('password-modal');
    const bankAccountModal = document.getElementById('bank-account-modal');
    
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function(e) {
            e.preventDefault();
            passwordModal.style.display = 'flex';
        });
    }
    
    // Bank account modal
    const addBankAccountBtn = document.getElementById('add-bank-account');
    const editBankAccountBtn = document.getElementById('edit-bank-account');
    
    if (addBankAccountBtn) {
        addBankAccountBtn.addEventListener('click', function(e) {
            e.preventDefault();
            bankAccountModal.style.display = 'flex';
        });
    }
    
    if (editBankAccountBtn) {
        editBankAccountBtn.addEventListener('click', function(e) {
            e.preventDefault();
            bankAccountModal.style.display = 'flex';
        });
    }
    
    // Close modal functionality
    const modalCloses = document.querySelectorAll('.rpp-modal-close, .rpp-modal-overlay');
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            passwordModal.style.display = 'none';
            bankAccountModal.style.display = 'none';
        });
    });
    
    // Refresh buttons
    const refreshTrackingBtn = document.getElementById('refresh-tracking');
    const refreshTeamBtn = document.getElementById('refresh-team');
    const refreshOrdersBtn = document.getElementById('refresh-orders');
    const refreshPayoutsBtn = document.getElementById('refresh-payouts');
    
    if (refreshTrackingBtn) {
        refreshTrackingBtn.addEventListener('click', function() {
            document.getElementById('tracking-content').dataset.loaded = '';
            loadTrackingData();
        });
    }
    
    if (refreshTeamBtn) {
        refreshTeamBtn.addEventListener('click', function() {
            document.getElementById('team-content').dataset.loaded = '';
            loadTeamData();
        });
    }
    
    if (refreshOrdersBtn) {
        refreshOrdersBtn.addEventListener('click', function() {
            document.getElementById('orders-content').dataset.loaded = '';
            loadOrdersData();
        });
    }
    
    if (refreshPayoutsBtn) {
        refreshPayoutsBtn.addEventListener('click', function() {
            document.getElementById('payouts-content').dataset.loaded = '';
            loadPayoutsData();
        });
    }
    
    // Payout request form
    const payoutForm = document.getElementById('payout-request-form');
    if (payoutForm) {
        payoutForm.addEventListener('submit', handlePayoutRequest);
    }
});

function loadTrackingData() {
    const container = document.getElementById('tracking-content');
    container.innerHTML = '<div style="text-align: center; padding: 40px;"><span class="spinner is-active"></span> Načítám tracking data...</div>';
    
    fetch(rpp_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'rpp_load_tracking_data',
            nonce: rpp_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            container.innerHTML = data.data;
            container.dataset.loaded = 'true';
        } else {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #c62828;">Chyba při načítání: ' + data.data + '</div>';
        }
    })
    .catch(error => {
        container.innerHTML = '<div style="text-align: center; padding: 40px; color: #c62828;">Chyba při komunikaci se serverem.</div>';
    });
}

function loadTeamData() {
    const container = document.getElementById('team-content');
    container.innerHTML = '<div style="text-align: center; padding: 40px;"><span class="spinner is-active"></span> Načítám data týmu...</div>';
    
    fetch(rpp_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'rpp_load_team_data',
            nonce: rpp_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            container.innerHTML = data.data;
            container.dataset.loaded = 'true';
        } else {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #c62828;">Chyba při načítání: ' + data.data + '</div>';
        }
    })
    .catch(error => {
        container.innerHTML = '<div style="text-align: center; padding: 40px; color: #c62828;">Chyba při komunikaci se serverem.</div>';
    });
}

function loadOrdersData() {
    const container = document.getElementById('orders-content');
    container.innerHTML = '<div style="text-align: center; padding: 40px;"><span class="spinner is-active"></span> Načítám objednávky...</div>';
    
    fetch(rpp_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'rpp_load_orders_data',
            nonce: rpp_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            container.innerHTML = data.data;
            container.dataset.loaded = 'true';
        } else {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #c62828;">Chyba při načítání: ' + data.data + '</div>';
        }
    })
    .catch(error => {
        container.innerHTML = '<div style="text-align: center; padding: 40px; color: #c62828;">Chyba při komunikaci se serverem.</div>';
    });
}

function loadPayoutsData() {
    const container = document.getElementById('payouts-content');
    container.innerHTML = '<div style="text-align: center; padding: 40px;"><span class="spinner is-active"></span> Načítám data výplat...</div>';
    
    fetch(rpp_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'rpp_load_payout_data',
            nonce: rpp_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            container.innerHTML = data.data.html || data.data;
            container.dataset.loaded = 'true';
            
            // Attach payout form handler if form exists
            const payoutForm = document.getElementById('rpp-payout-request-form');
            if (payoutForm) {
                payoutForm.addEventListener('submit', handlePayoutRequest);
            }
        } else {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #c62828;">Chyba při načítání: ' + (data.data || 'Neznámá chyba') + '</div>';
        }
    })
    .catch(error => {
        container.innerHTML = '<div style="text-align: center; padding: 40px; color: #c62828;">Chyba při komunikaci se serverem</div>';
    });
}

function handlePayoutRequest(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'rpp_request_payout');
    formData.append('nonce', rpp_ajax.nonce);
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Odesílám...';
    
    fetch(rpp_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Žádost o výplatu byla odeslána!', 'success');
            // Reload payout data
            document.getElementById('payouts-content').dataset.loaded = '';
            loadPayoutsData();
        } else {
            showNotification('Chyba: ' + data.data, 'error');
        }
    })
    .catch(error => {
        showNotification('Chyba při komunikaci se serverem', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// Bank account form submission
document.getElementById('bank-account-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'rpp_update_bank_account');
    formData.append('nonce', rpp_ajax.nonce);
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Ukládám...';
    
    fetch(rpp_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Číslo účtu bylo uloženo!', 'success');
            closeBankAccountModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Chyba: ' + data.data, 'error');
        }
    })
    .catch(error => {
        showNotification('Chyba při komunikaci se serverem', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
});

function copyReferralLink() {
    const linkInput = document.getElementById('referral-link');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999);
    
    navigator.clipboard.writeText(linkInput.value).then(function() {
        showNotification('Odkaz zkopírován!', 'success');
    });
}

function generateCustomLink() {
    const customUrl = document.getElementById('custom-page').value;
    if (!customUrl) {
        showNotification('Prosím zadejte URL', 'error');
        return;
    }
    
    const partnerCode = '<?php echo esc_js($partner->partner_code); ?>';
    const separator = customUrl.includes('?') ? '&' : '?';
    const customLink = customUrl + separator + 'ref=' + partnerCode;
    
    document.getElementById('referral-link').value = customLink;
    showNotification('Vlastní odkaz vygenerován!', 'success');
}

function shareOnSocial(platform) {
    const link = document.getElementById('referral-link').value;
    const text = 'Podívejte se na tuto skvělou příležitost!';
    let url;
    
    switch (platform) {
        case 'facebook':
            url = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(link);
            break;
        case 'twitter':
            url = 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(link) + '&text=' + encodeURIComponent(text);
            break;
        case 'email':
            url = 'mailto:?subject=' + encodeURIComponent(text) + '&body=' + encodeURIComponent(text + ' ' + link);
            break;
        default:
            return;
    }
    
    if (platform === 'email') {
        window.location.href = url;
    } else {
        window.open(url, '_blank', 'width=600,height=400');
    }
}

function closePasswordModal() {
    document.getElementById('password-modal').style.display = 'none';
}

function closeBankAccountModal() {
    document.getElementById('bank-account-modal').style.display = 'none';
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 4px;
        color: white;
        font-weight: 600;
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    if (type === 'success') {
        notification.style.background = '#28a745';
    } else {
        notification.style.background = '#dc3545';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
</script>