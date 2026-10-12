<script id="scripts">
(function() {
    var toast = window.xcToast || function(msg, type) {
        if (window.Swal) {
            Swal.fire({ text: msg, icon: (type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success')) });
        } else {
            alert(msg);
        }
    };

    var confirmDialog = window.xcConfirm || function(msg) {
        return Promise.resolve(confirm(msg));
    };

    $(function() {
        if ($.fn.dataTable) {
            $.fn.dataTable.ext.errMode = 'none';
        }

        if ($.fn.DataTable && $('#resellerMethodsTable').length) {
            $('#resellerMethodsTable').DataTable({
                responsive: true,
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: 'no-sort', orderable: false, searchable: false }
                ],
                pageLength: 25,
                language: {
                    emptyTable: '<div class="d-flex flex-column align-items-center justify-content-center text-center py-4"><div class="avatar avatar-xl bg-label-warning rounded-circle mb-3 d-flex align-items-center justify-content-center shadow-sm"><i class="ti tabler-wallet-off fs-1 text-warning"></i></div><h5 class="fw-bold mb-1 text-dark">No Payment Methods Yet!</h5><p class="text-muted mb-3" style="max-width: 480px;">Create your first payment method so your sub-resellers can start buying points from you.</p></div>'
                },
                layout: {
                    topStart: {
                        pageLength: {},
                        buttons: [
                            { extend: 'copy', className: 'btn btn-sm btn-label-secondary me-1', text: '<i class="ti tabler-copy me-1"></i>Copy' },
                            { extend: 'csv', className: 'btn btn-sm btn-label-primary me-1', text: '<i class="ti tabler-file-spreadsheet me-1"></i>CSV' },
                            { extend: 'excel', className: 'btn btn-sm btn-label-success me-1', text: '<i class="ti tabler-file-analytics me-1"></i>Excel' },
                            { extend: 'pdf', className: 'btn btn-sm btn-label-danger me-1', text: '<i class="ti tabler-file-text me-1"></i>PDF' },
                            { extend: 'print', className: 'btn btn-sm btn-label-info', text: '<i class="ti tabler-printer me-1"></i>Print' }
                        ]
                    },
                    topEnd: 'search'
                }
            });
        }

        const formModalEl = document.getElementById('resMethodModal');
        const formModal = formModalEl ? new bootstrap.Modal(formModalEl) : null;
        const prevModalEl = document.getElementById('previewInstructionsModal');
        const prevModal = prevModalEl ? new bootstrap.Modal(prevModalEl) : null;
        const iconModalEl = document.getElementById('iconPickerModal');
        const iconModal = iconModalEl ? new bootstrap.Modal(iconModalEl) : null;
        const form = document.getElementById('resMethodForm');
        const iconInput = document.getElementById('resMethodIcon');
        const iconPreview = document.getElementById('resIconPreview');

        // Integrated Tabler Icon Catalog
        const PAYMENT_ICONS = [
            // Cash & Pay
            { icon: 'ti tabler-credit-card', name: 'Credit Card', cat: 'payment', tags: 'card visa mastercard payment debit checkout' },
            { icon: 'ti tabler-credit-card-filled', name: 'Credit Card (Solid)', cat: 'payment', tags: 'card payment debit' },
            { icon: 'ti tabler-credit-card-pay', name: 'Credit Card Pay', cat: 'payment', tags: 'card pay pos checkout terminal' },
            { icon: 'ti tabler-credit-card-refund', name: 'Card Refund', cat: 'payment', tags: 'refund return chargeback reversal' },
            { icon: 'ti tabler-credit-card-off', name: 'Card Disabled', cat: 'payment', tags: 'card disabled block' },
            { icon: 'ti tabler-cash', name: 'Paper Cash', cat: 'payment', tags: 'cash physical bill money fiat currency' },
            { icon: 'ti tabler-cash-banknote', name: 'Banknote', cat: 'payment', tags: 'money note currency bill' },
            { icon: 'ti tabler-coins', name: 'Coins Stash', cat: 'payment', tags: 'coins change cents gold points' },
            { icon: 'ti tabler-coin', name: 'Single Coin', cat: 'payment', tags: 'coin point credit gold token' },
            { icon: 'ti tabler-wallet', name: 'Digital Wallet', cat: 'payment', tags: 'wallet ewallet balance cash account' },
            { icon: 'ti tabler-wallet-off', name: 'Wallet Off', cat: 'payment', tags: 'wallet disabled closed' },
            { icon: 'ti tabler-device-mobile', name: 'Vodafone / Mobile Cash', cat: 'payment', tags: 'vodafone orang we etisalat fawry mobile cash wallet' },
            { icon: 'ti tabler-device-mobile-message', name: 'Mobile Transfer SMS', cat: 'payment', tags: 'vodafone cash instapay mobile sms transfer text' },
            { icon: 'ti tabler-device-mobile-charging', name: 'Mobile Top-up', cat: 'payment', tags: 'recharge topup mobile pay credit' },
            { icon: 'ti tabler-qrcode', name: 'QR Code Pay', cat: 'payment', tags: 'qr code scan bar instapay pix upi quick' },
            { icon: 'ti tabler-scan', name: 'Barcode / Scan', cat: 'payment', tags: 'scan pos terminal pos checkout barcode' },
            { icon: 'ti tabler-barcode', name: 'Barcode', cat: 'payment', tags: 'barcode receipt code bill pos' },
            { icon: 'ti tabler-receipt', name: 'Payment Receipt', cat: 'payment', tags: 'receipt bill payment voucher proof slip' },
            { icon: 'ti tabler-receipt-2', name: 'Invoice Slip', cat: 'payment', tags: 'invoice bill receipt transfer statement' },
            { icon: 'ti tabler-receipt-refund', name: 'Receipt Refund', cat: 'payment', tags: 'refund receipt reversal' },
            { icon: 'ti tabler-send', name: 'Instant Send', cat: 'payment', tags: 'send instapay instant fast transfer p2p' },
            { icon: 'ti tabler-send-2', name: 'Instant Send Fast', cat: 'payment', tags: 'send remittance payment fast' },
            { icon: 'ti tabler-transfer', name: 'Transfer Wire', cat: 'payment', tags: 'transfer wire remit bank p2p transaction' },
            { icon: 'ti tabler-transfer-in', name: 'Deposit / Inflow', cat: 'payment', tags: 'deposit topup in transfer credit' },
            { icon: 'ti tabler-transfer-out', name: 'Withdrawal', cat: 'payment', tags: 'withdraw payout out transfer debit' },
            { icon: 'ti tabler-arrows-exchange', name: 'Exchange / Swap', cat: 'payment', tags: 'transfer swap exchange remittance p2p' },

            // Bank & Wallet
            { icon: 'ti tabler-building-bank', name: 'Bank Wire / Branch', cat: 'banking', tags: 'bank wire transfer iban swift ach branch deposit' },
            { icon: 'ti tabler-file-invoice', name: 'Bank Invoice', cat: 'banking', tags: 'invoice bank bill account statement' },
            { icon: 'ti tabler-report-money', name: 'Financial Statement', cat: 'banking', tags: 'finance report profit audit balance account' },
            { icon: 'ti tabler-pig-money', name: 'Savings / Deposit', cat: 'banking', tags: 'savings pig money deposit interest fund' },
            { icon: 'ti tabler-moneybag', name: 'Money Bag', cat: 'banking', tags: 'moneybag bag cash sack wealth treasure' },
            { icon: 'ti tabler-shield-check', name: 'Secure Banking', cat: 'banking', tags: 'security verified safe protected shield escrow' },
            { icon: 'ti tabler-lock', name: 'Escrow Vault', cat: 'banking', tags: 'lock secure escrow safe vault trust' },
            { icon: 'ti tabler-certificate', name: 'Official Certificate', cat: 'banking', tags: 'verified certificate official badge license' },

            // Crypto & Currencies
            { icon: 'ti tabler-currency-bitcoin', name: 'Bitcoin (BTC)', cat: 'crypto', tags: 'btc crypto bitcoin blockchain satoshi' },
            { icon: 'ti tabler-coin-bitcoin', name: 'BTC Gold Coin', cat: 'crypto', tags: 'btc coin crypto' },
            { icon: 'ti tabler-receipt-bitcoin', name: 'Bitcoin Receipt', cat: 'crypto', tags: 'btc tx hash receipt' },
            { icon: 'ti tabler-currency-ethereum', name: 'Ethereum (ETH)', cat: 'crypto', tags: 'eth ether erc20 smart contract' },
            { icon: 'ti tabler-diamond', name: 'Tether (USDT TRC20)', cat: 'crypto', tags: 'usdt trc20 erc20 stablecoin crypto tether diamond tron' },
            { icon: 'ti tabler-currency-solana', name: 'Solana (SOL)', cat: 'crypto', tags: 'solana sol crypto spl' },
            { icon: 'ti tabler-currency-dogecoin', name: 'Dogecoin (DOGE)', cat: 'crypto', tags: 'doge dogecoin meme crypto' },
            { icon: 'ti tabler-currency-litecoin', name: 'Litecoin (LTC)', cat: 'crypto', tags: 'ltc litecoin crypto' },
            { icon: 'ti tabler-currency-monero', name: 'Monero (XMR)', cat: 'crypto', tags: 'xmr monero private crypto' },
            { icon: 'ti tabler-currency-ripple', name: 'Ripple (XRP)', cat: 'crypto', tags: 'xrp ripple crypto ledger' },
            { icon: 'ti tabler-chart-candle', name: 'Crypto Trading', cat: 'crypto', tags: 'trading candles market exchange' },
            { icon: 'ti tabler-currency-dollar', name: 'US Dollar ($)', cat: 'crypto', tags: 'usd dollar buck money fiat usa' },
            { icon: 'ti tabler-currency-euro', name: 'Euro (€)', cat: 'crypto', tags: 'eur euro europe fiat money' },
            { icon: 'ti tabler-currency-pound', name: 'British Pound (£)', cat: 'crypto', tags: 'gbp pound sterling uk fiat' },
            { icon: 'ti tabler-currency-riyal', name: 'Saudi Riyal (SAR)', cat: 'crypto', tags: 'sar riyal saudi ksa gulf arab' },
            { icon: 'ti tabler-currency-dirham', name: 'UAE Dirham (AED)', cat: 'crypto', tags: 'aed dirham emirati uae dubai gulf' },
            { icon: 'ti tabler-currency-dinar', name: 'Dinar (KWD/IQD/JOD)', cat: 'crypto', tags: 'kwd iqd jod dinar kuwait iraq jordan' },
            { icon: 'ti tabler-currency-bahraini', name: 'Bahraini Dinar (BHD)', cat: 'crypto', tags: 'bhd bahrain dinar gulf' },
            { icon: 'ti tabler-currency-rubel', name: 'Russian Ruble (RUB)', cat: 'crypto', tags: 'rub ruble russia money fiat' },
            { icon: 'ti tabler-currency-lira', name: 'Turkish Lira (TRY)', cat: 'crypto', tags: 'try lira turkey money fiat' },
            { icon: 'ti tabler-currency-shekel', name: 'Shekel (ILS)', cat: 'crypto', tags: 'ils shekel money fiat' },
            { icon: 'ti tabler-currency-yen', name: 'Japanese Yen (JPY)', cat: 'crypto', tags: 'jpy yen japan money fiat' },
            { icon: 'ti tabler-currency-yuan', name: 'Chinese Yuan (CNY)', cat: 'crypto', tags: 'cny yuan china money fiat' },
            { icon: 'ti tabler-currency-won', name: 'Korean Won (KRW)', cat: 'crypto', tags: 'krw won korea money fiat' },
            { icon: 'ti tabler-currency-rupee', name: 'Indian Rupee (INR)', cat: 'crypto', tags: 'inr rupee india upi fiat' },
            { icon: 'ti tabler-currency-real', name: 'Brazilian Real (BRL)', cat: 'crypto', tags: 'brl real brazil pix fiat' },

            // Brands
            { icon: 'ti tabler-brand-paypal', name: 'PayPal', cat: 'brands', tags: 'paypal gateway checkout online wallet' },
            { icon: 'ti tabler-brand-stripe', name: 'Stripe', cat: 'brands', tags: 'stripe card gateway checkout processing' },
            { icon: 'ti tabler-brand-mastercard', name: 'Mastercard', cat: 'brands', tags: 'mastercard master card debit credit' },
            { icon: 'ti tabler-brand-apple', name: 'Apple Pay', cat: 'brands', tags: 'apple pay ios iphone mac wallet' },
            { icon: 'ti tabler-brand-google', name: 'Google Pay', cat: 'brands', tags: 'google pay android gpay wallet' },
            { icon: 'ti tabler-brand-cashapp', name: 'Cash App', cat: 'brands', tags: 'cashapp square p2p transfer' },
            { icon: 'ti tabler-brand-alipay', name: 'Alipay', cat: 'brands', tags: 'alipay china ant financial qr' },
            { icon: 'ti tabler-brand-telegram', name: 'Telegram / TON', cat: 'brands', tags: 'telegram ton wallet p2p bot crypto' }
        ];

        const iconGrid = document.getElementById('iconLibraryGrid') || document.getElementById('iconGrid');
        const iconSearch = document.getElementById('iconSearchInput');
        let currentResIconCat = 'all';

        function renderIcons(query = '', category = 'all') {
            if (!iconGrid) return;
            iconGrid.innerHTML = '';
            const q = query.toLowerCase().trim();
            const filtered = PAYMENT_ICONS.filter(item => {
                const matchesCat = (category === 'all' || item.cat === category);
                const matchesQuery = (!q || item.name.toLowerCase().includes(q) || item.tags.toLowerCase().includes(q) || item.icon.toLowerCase().includes(q));
                return matchesCat && matchesQuery;
            });

            if (filtered.length === 0) {
                iconGrid.innerHTML = '<div class="col-12 text-center py-5 text-muted"><i class="ti tabler-mood-empty fs-1 mb-2"></i><p>No matching icons found.</p></div>';
                return;
            }

            filtered.forEach(item => {
                const col = document.createElement('div');
                col.className = 'col-4 col-sm-3 col-md-2';
                const isSelected = (iconInput.value === item.icon);
                col.innerHTML = `
                    <div class="card h-100 p-2 text-center border icon-choice-item ${isSelected ? 'border-primary bg-label-primary shadow-sm' : 'bg-light'} cursor-pointer" data-icon="${item.icon}" style="cursor: pointer; transition: all 0.15s ease;">
                        <div class="py-2"><i class="${item.icon} fs-2 text-primary"></i></div>
                        <small class="text-truncate d-block fw-semibold text-dark" style="font-size: 0.72rem;" title="${item.name}">${item.name}</small>
                    </div>
                `;
                col.querySelector('.icon-choice-item').addEventListener('click', function() {
                    const chosen = this.getAttribute('data-icon');
                    iconInput.value = chosen;
                    iconPreview.className = chosen + ' fs-4 text-primary';
                    if (iconModal) iconModal.hide();
                });
                iconGrid.appendChild(col);
            });
        }

        $('.icon-cat-filter').on('click', function() {
            $('.icon-cat-filter').removeClass('btn-primary active').addClass('btn-outline-secondary');
            $(this).removeClass('btn-outline-secondary').addClass('btn-primary active');
            currentResIconCat = $(this).data('cat') || 'all';
            renderIcons(iconSearch ? iconSearch.value : '', currentResIconCat);
        });

        $('#btnOpenIconPicker, #btnResIconPreviewTrigger, #resIconPreview').on('click', function() {
            renderIcons(iconSearch ? iconSearch.value : '', currentResIconCat);
            if (iconModal) iconModal.show();
        });

        if (iconSearch) {
            iconSearch.addEventListener('input', function() {
                renderIcons(this.value, currentResIconCat);
            });
        }

        // Stacking handlers to ensure icon picker appears above the method modal and dims it
        $('#iconPickerModal').on('show.bs.modal', function() {
            $(this).css('z-index', 1105);
        });
        $('#iconPickerModal').on('shown.bs.modal', function() {
            const backdrops = $('.modal-backdrop');
            if (backdrops.length > 1) {
                backdrops.last().css('z-index', 1100);
            }
        });
        $('#iconPickerModal').on('hidden.bs.modal', function() {
            if ($('#resMethodModal').hasClass('show')) {
                $('body').addClass('modal-open');
            }
        });

        // Dual-Mode Visual & HTML Editor
        const visualCanvas = document.getElementById('resVisualCanvas') || document.getElementById('visualCanvas');
        const htmlSource = document.getElementById('resHtmlSource') || document.getElementById('htmlSource');
        const visualCol = document.getElementById('resVisualCol') || document.getElementById('visualCol');
        const htmlCol = document.getElementById('resHtmlCol') || document.getElementById('htmlCol');
        const livePreviewContainer = document.getElementById('resLivePreviewContainer') || document.getElementById('livePreviewContainer');
        const livePreviewBody = document.getElementById('resLivePreviewBody') || document.getElementById('livePreviewBody');
        const hiddenInstructions = document.getElementById('resMethodInstructions');

        let isUpdatingSync = false;
        let editorMode = 'split';

		// Helper: Decode HTML entity-encoded markup into true HTML tags
		function decodeHtmlEntities(str) {
			if (!str) return '';
			let content = String(str).trim();
			if (/&lt;[a-z!/]/i.test(content) || (content.includes('&lt;') && content.includes('&gt;'))) {
				const txt = document.createElement('textarea');
				txt.innerHTML = content;
				content = txt.value;
				if (/&lt;[a-z!/]/i.test(content) || (content.includes('&lt;') && content.includes('&gt;'))) {
					txt.innerHTML = content;
					content = txt.value;
				}
			}
			return content;
		}

		// Dynamic script and style re-hydration engine for previewing rich HTML/CSS/JS widgets
		function renderDynamicInstructions(container, htmlContent) {
			if (!container) return;
			let content = decodeHtmlEntities(htmlContent || '');
			if (!content || !content.trim()) {
				container.innerHTML = '<p class="text-muted fst-italic">No instructions entered yet.</p>';
				return;
			}

			// If plain text with no HTML markup, preserve newlines cleanly
			if (!/<[a-z][\s\S]*>/i.test(content)) {
				content = '<div style="white-space: pre-wrap; line-height: 1.6;">' +
					content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') +
					'</div>';
			}

			container.innerHTML = content;

			const scripts = Array.from(container.querySelectorAll('script'));
			scripts.forEach(oldScript => {
				const newScript = document.createElement('script');
				Array.from(oldScript.attributes).forEach(attr => {
					newScript.setAttribute(attr.name, attr.value);
				});
				if (oldScript.src) {
					newScript.src = oldScript.src;
				} else {
					newScript.text = oldScript.textContent || oldScript.innerText || '';
				}
				if (oldScript.parentNode) {
					oldScript.parentNode.replaceChild(newScript, oldScript);
				} else {
					container.appendChild(newScript);
				}
			});
		}

		const dualEditor = {
			getHtml: function() {
				let val = '';
				if (editorMode === 'html' || (htmlSource && htmlSource.value)) {
					val = htmlSource.value;
				} else {
					val = visualCanvas ? visualCanvas.innerHTML : '';
				}
				return decodeHtmlEntities(val);
			},
			setHtml: function(html) {
				html = decodeHtmlEntities(html || '');
				if (visualCanvas) visualCanvas.innerHTML = html;
				if (htmlSource) htmlSource.value = html;
				if (livePreviewBody) renderDynamicInstructions(livePreviewBody, html);
				if (hiddenInstructions) hiddenInstructions.value = html;
			},
			syncVisualToHtml: function() {
				if (isUpdatingSync || !visualCanvas) return;
				isUpdatingSync = true;
				const html = visualCanvas.innerHTML;
				if (htmlSource) htmlSource.value = html;
				if (livePreviewBody) renderDynamicInstructions(livePreviewBody, html);
				if (hiddenInstructions) hiddenInstructions.value = html;
				isUpdatingSync = false;
			},
			syncHtmlToVisual: function() {
				if (isUpdatingSync || !htmlSource) return;
				isUpdatingSync = true;
				const html = htmlSource.value;
				if (visualCanvas) visualCanvas.innerHTML = html;
				if (livePreviewBody) renderDynamicInstructions(livePreviewBody, html);
				if (hiddenInstructions) hiddenInstructions.value = html;
				isUpdatingSync = false;
			},
			exec: function(command, value = null) {
				if (!visualCanvas) return;
				visualCanvas.focus();
				document.execCommand(command, false, value);
				this.syncVisualToHtml();
			},
			insertHtml: function(htmlSnippet) {
				if (!visualCanvas) return;
				visualCanvas.focus();
				var inserted = false;
				try {
					inserted = document.execCommand('insertHTML', false, htmlSnippet);
				} catch(e) {}
				if (!inserted) {
					visualCanvas.innerHTML += htmlSnippet;
				}
				this.syncVisualToHtml();
			},
			setMode: function(mode) {
				editorMode = mode;
				$('.res-editor-mode-btn').each(function() {
					const m = $(this).data('mode');
					if (m === mode) {
						$(this).addClass('btn-primary active').removeClass('btn-outline-primary');
					} else {
						$(this).removeClass('btn-primary active').addClass('btn-outline-primary');
					}
				});

				if (mode === 'split') {
					if (visualCol) visualCol.className = 'col-md-6 border-end d-block';
					if (htmlCol) htmlCol.className = 'col-md-6 d-block';
					if (livePreviewContainer) livePreviewContainer.classList.add('d-none');
					this.syncVisualToHtml();
				} else if (mode === 'visual') {
					if (visualCol) visualCol.className = 'col-12 d-block';
					if (htmlCol) htmlCol.className = 'd-none';
					if (livePreviewContainer) livePreviewContainer.classList.add('d-none');
				} else if (mode === 'html') {
					if (visualCol) visualCol.className = 'd-none';
					if (htmlCol) htmlCol.className = 'col-12 d-block';
					if (livePreviewContainer) livePreviewContainer.classList.add('d-none');
					this.syncVisualToHtml();
				} else if (mode === 'preview') {
					if (visualCol) visualCol.className = 'd-none';
					if (htmlCol) htmlCol.className = 'd-none';
					if (livePreviewContainer) livePreviewContainer.classList.remove('d-none');
					if (livePreviewBody) renderDynamicInstructions(livePreviewBody, this.getHtml());
				}
			}
		};

		// Real-time bidirectional synchronization
		if (visualCanvas) {
			visualCanvas.addEventListener('input', () => dualEditor.syncVisualToHtml());
			visualCanvas.addEventListener('keyup', () => dualEditor.syncVisualToHtml());
			visualCanvas.addEventListener('paste', function(e) {
				const text = (e.clipboardData || window.clipboardData).getData('text');
				if (text && /<[a-z][\s\S]*>/i.test(text)) {
					e.preventDefault();
					document.execCommand('insertHTML', false, text);
					dualEditor.syncVisualToHtml();
				} else {
					setTimeout(() => dualEditor.syncVisualToHtml(), 50);
				}
			});
		}
        if (htmlSource) {
            htmlSource.addEventListener('input', () => dualEditor.syncHtmlToVisual());
            htmlSource.addEventListener('keyup', () => dualEditor.syncHtmlToVisual());
            htmlSource.addEventListener('paste', () => setTimeout(() => dualEditor.syncHtmlToVisual(), 50));
        }

        // Mode switch buttons
        $('.res-editor-mode-btn').on('click', function(e) {
            e.preventDefault();
            const mode = $(this).data('mode') || 'split';
            dualEditor.setMode(mode);
        });

        // Format actions (prevent mousedown focus loss)
        $('.res-cmd-btn').on('mousedown', function(e) {
            e.preventDefault();
        }).on('click', function(e) {
            e.preventDefault();
            const cmd = $(this).data('cmd');
            dualEditor.exec(cmd);
        });

        $('.res-format-btn').on('mousedown', function(e) {
            e.preventDefault();
        }).on('click', function(e) {
            e.preventDefault();
            const tag = $(this).data('tag');
            dualEditor.exec('formatBlock', tag);
        });

        $('.res-color-btn').on('mousedown', function(e) {
            e.preventDefault();
        }).on('click', function(e) {
            e.preventDefault();
            const color = $(this).data('color');
            dualEditor.exec('foreColor', color);
        });

        $('#btnResInsertLink').on('mousedown', function(e) {
            e.preventDefault();
        }).on('click', function(e) {
            e.preventDefault();
            const url = prompt('Enter Web Link / URL (e.g. https://t.me/yourusername):', 'https://');
            if (url && url !== 'https://') {
                dualEditor.exec('createLink', url);
            }
        });

        // Presets Templates
        const RES_TEMPLATES = {
            vodafone: `<div class="card border border-danger mb-3 p-3 bg-light">
    <div class="d-flex align-items-center mb-2">
        <i class="ti tabler-device-mobile text-danger fs-3 me-2"></i>
        <h5 class="mb-0 text-danger fw-bold">Vodafone Cash Transfer</h5>
    </div>
    <div class="mb-2"><strong>Wallet Number:</strong> <code class="fs-6 text-dark fw-bold">010XXXXXXXX</code></div>
    <div class="mb-2"><strong>Account Name:</strong> Account Name</div>
    <div class="alert alert-warning py-1 px-2 mb-0 small"><i class="ti tabler-alert-triangle me-1"></i> Send payment + transfer fees. Upload SMS receipt below.</div>
</div><p></p>`,
            instapay: `<div class="card border border-info mb-3 p-3 bg-light">
    <div class="d-flex align-items-center mb-2">
        <i class="ti tabler-send text-info fs-3 me-2"></i>
        <h5 class="mb-0 text-info fw-bold">InstaPay Instant Transfer</h5>
    </div>
    <div class="mb-2"><strong>Payment Address (IPA):</strong> <code class="fs-6 text-dark fw-bold">username@instapay</code></div>
    <div class="mb-2"><strong>Mobile Number:</strong> <code class="fs-6 text-dark fw-bold">010XXXXXXXX</code></div>
    <div class="mb-2"><strong>Bank Name:</strong> National Bank of Egypt (NBE)</div>
    <div class="alert alert-info py-1 px-2 mb-0 small"><i class="ti tabler-info-circle me-1"></i> Instant verification. Please upload transaction receipt screenshot after sending.</div>
</div><p></p>`,
            bank: `<div class="card border border-primary mb-3 p-3 bg-light">
    <div class="d-flex align-items-center mb-2">
        <i class="ti tabler-building-bank text-primary fs-3 me-2"></i>
        <h5 class="mb-0 text-primary fw-bold">Bank Wire / IBAN Details</h5>
    </div>
    <div class="mb-2"><strong>Bank Name:</strong> National Bank / Commercial Bank</div>
    <div class="mb-2"><strong>Beneficiary:</strong> Official Company / Personal Name</div>
    <div class="mb-2"><strong>Account Number:</strong> <code>12345678901234</code></div>
    <div class="mb-2"><strong>IBAN:</strong> <code class="text-primary fw-bold">EG000000000000000000000000000</code></div>
    <div class="mb-2"><strong>SWIFT / BIC:</strong> <code>NBEFEGCX</code></div>
</div><p></p>`,
            crypto: `<style>
.widget-crypto {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 20px;
    color: #f8fafc;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}
.widget-crypto-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}
.widget-crypto-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #38bdf8;
    margin: 0;
}
.widget-crypto-badge {
    background: #0284c7;
    color: #fff;
    font-size: 0.75rem;
    padding: 3px 8px;
    border-radius: 4px;
    font-weight: 600;
}
.widget-crypto-box {
    background: #090d16;
    border: 1px dashed #475569;
    border-radius: 8px;
    padding: 12px;
    margin: 12px 0;
}
.widget-crypto-addr {
    font-family: monospace;
    font-size: 0.95rem;
    color: #38bdf8;
    word-break: break-all;
    display: block;
    user-select: all;
}
.widget-copy-btn {
    background: #0ea5e9;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.widget-copy-btn:hover {
    background: #0284c7;
}
.widget-notice {
    font-size: 0.85rem;
    color: #94a3b8;
    margin-top: 10px;
}
</style>
<div class="widget-crypto">
    <div class="widget-crypto-header">
        <i class="ti tabler-diamond fs-3 text-info"></i>
        <h5 class="widget-crypto-title">USDT (TRC-20) Payment</h5>
        <span class="widget-crypto-badge">TRON ONLY</span>
    </div>
    <p style="margin: 0 0 8px 0; color: #cbd5e1; font-size: 0.9rem;">Please transfer the exact order amount to the official deposit address below:</p>
    <div class="widget-crypto-box">
        <span class="widget-crypto-addr" id="cryptoDepositAddress">TXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</span>
    </div>
    <div style="display: flex; align-items: center; gap: 12px;">
        <button type="button" class="widget-copy-btn" id="btnCopyCryptoAddr" onclick="copyCryptoWidgetAddress()">
            <i class="ti tabler-copy"></i> Copy Address
        </button>
        <span id="copyCryptoFeedback" style="display: none; color: #4ade80; font-weight: 600; font-size: 0.85rem;">
            <i class="ti tabler-check"></i> Address Copied!
        </span>
    </div>
    <div class="widget-notice">
        ⚠️ <strong>Important:</strong> Only send USDT on Tron (TRC20). Upload transaction screenshot below.
    </div>
</div>
<script>
function copyCryptoWidgetAddress() {
    var addrEl = document.getElementById('cryptoDepositAddress');
    var text = addrEl ? addrEl.innerText.trim() : '';
    if (!text) return;
    navigator.clipboard.writeText(text).then(function() {
        var fb = document.getElementById('copyCryptoFeedback');
        if (fb) {
            fb.style.display = 'inline-flex';
            setTimeout(function() { fb.style.display = 'none'; }, 2500);
        }
    }).catch(function() {
        alert('Copied: ' + text);
    });
}
<\/script>`
        };

        $('.res-template-btn').on('click', function(e) {
            e.preventDefault();
            const tpl = $(this).data('template');
            if (RES_TEMPLATES[tpl]) {
                const current = dualEditor.getHtml().trim();
                if (!current || confirm('Load this preset template? It will replace current instructions.')) {
                    dualEditor.setHtml(RES_TEMPLATES[tpl]);
                }
            }
        });

        // Snippets
        const RES_SNIPPETS = {
            copybox: `<div class="border rounded p-2 bg-light my-2 d-flex align-items-center justify-content-between"><div><small class="text-muted d-block">Account / Number:</small><code class="fs-6 fw-bold">010XXXXXXXX</code></div></div><p></p>`,
            alert: `<div class="alert alert-warning py-2 px-3 my-2 border"><i class="ti tabler-alert-triangle me-1"></i> <strong>Important:</strong> Please include your username or Order ID in transfer notes and attach receipt slip.</div><p></p>`
        };

        $('.res-snippet-btn').on('click', function(e) {
            e.preventDefault();
            const snip = $(this).data('snippet');
            if (RES_SNIPPETS[snip]) {
                dualEditor.insertHtml(RES_SNIPPETS[snip]);
            }
        });

        // View instructions modal
        $(document).on('click', '.view-inst-btn', function() {
            const id = this.getAttribute('data-id');
            const rawEl = id ? document.getElementById('res-inst-raw-' + id) : null;
            const name = this.getAttribute('data-name') || '';
            const rawVal = (rawEl && rawEl.value) ? rawEl.value : (this.getAttribute('data-inst') || '');
            const inst = decodeHtmlEntities(rawVal);
            const titleEl = document.getElementById('prevModalMethodName') || document.getElementById('prevModalName');
            if (titleEl) {
                titleEl.textContent = name;
            }
            const contentEl = document.getElementById('prevModalContent');
            if (contentEl) {
                renderDynamicInstructions(contentEl, inst);
            }
            if (prevModal) prevModal.show();
        });

        $('#btnAddNewMethod').on('click', function() {
            if (!form) return;
            form.reset();
            document.getElementById('resMethodId').value = '';
            document.getElementById('resMethodModalTitle').innerHTML = '<i class="ti tabler-plus me-2"></i>Add Payment Method';
            document.getElementById('resMethodActive').checked = true;
            document.getElementById('resIconPreview').className = 'ti tabler-credit-card fs-4 text-primary';
            dualEditor.setHtml('');
            if (formModal) formModal.show();
        });

        $(document).on('click', '.edit-res-method', function() {
            const d = this.dataset;
            const rawEl = d.id ? document.getElementById('res-inst-raw-' + d.id) : null;
            const rawVal = (rawEl && rawEl.value) ? rawEl.value : (d.instructions || '');
            const inst = decodeHtmlEntities(rawVal);
            document.getElementById('resMethodId').value = d.id;
            document.getElementById('resMethodModalTitle').innerHTML = '<i class="ti tabler-edit me-2"></i>Edit Method: ' + d.name;
            document.getElementById('resMethodName').value = d.name;
            document.getElementById('resMethodSlug').value = d.slug;
            document.getElementById('resMethodIcon').value = d.icon;
            document.getElementById('resMethodRate').value = d.rate;
            document.getElementById('resMethodCurrency').value = d.currency;
            document.getElementById('resMethodMin').value = d.min;
            document.getElementById('resMethodMax').value = d.max;
            document.getElementById('resMethodActive').checked = (d.active === '1');
            document.getElementById('resIconPreview').className = (d.icon || 'ti tabler-credit-card') + ' fs-4 text-primary';
            dualEditor.setHtml(inst);
            if (formModal) formModal.show();
        });

        // Save Method
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                document.getElementById('resMethodInstructions').value = dualEditor.getHtml();
                const formData = new FormData(form);
                const methodId = document.getElementById('resMethodId').value;
                const url = 'api?action=billing_save_method' + (methodId ? '&id=' + encodeURIComponent(methodId) : '');

                fetch(url, { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            if (formModal) formModal.hide();
                            toast('Payment method saved successfully!', 'success');
                            setTimeout(() => location.reload(), 600);
                        } else {
                            toast(res.message || 'Error saving payment method', 'error');
                        }
                    })
                    .catch(() => toast('Network error while saving.', 'error'));
            });
        }

        // Delete Method
        $(document).on('click', '.delete-res-method', function() {
            const id = this.dataset.id;
            confirmDialog('Are you sure you want to delete this payment method?').then(function(ok) {
                if (!ok) return;
                fetch('api?action=billing_delete_method&id=' + encodeURIComponent(id), { method: 'POST' })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            toast('Payment method deleted successfully.', 'success');
                            setTimeout(() => location.reload(), 600);
                        } else {
                            toast(res.message || 'Error deleting method', 'error');
                        }
                    })
                    .catch(() => toast('Network error while deleting.', 'error'));
            });
        });
    });
})();
</script>
</body>
</html>
