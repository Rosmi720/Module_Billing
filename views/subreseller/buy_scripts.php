<script id="scripts">
(function() {
    var toast = window.xcToast || function(msg, type) {
        if (window.Swal) {
            Swal.fire({ text: msg, icon: (type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success')) });
        } else {
            alert(msg);
        }
    };

    $(function() {
        let currentStep = 1;
        let selectedMethod = null;

        const inputPoints = document.getElementById('inputPoints');
        const selectedMethodId = document.getElementById('selectedMethodId');

        // Preset buttons
        document.querySelectorAll('.preset-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (inputPoints) inputPoints.value = this.dataset.val;
            });
        });

        const btnMinus = document.getElementById('btnMinusPoints');
        if (btnMinus) {
            btnMinus.addEventListener('click', function() {
                let v = parseInt(inputPoints.value) || 1;
                if (v > 10) inputPoints.value = v - 10;
                else if (v > 1) inputPoints.value = v - 1;
            });
        }

        const btnPlus = document.getElementById('btnPlusPoints');
        if (btnPlus) {
            btnPlus.addEventListener('click', function() {
                let v = parseInt(inputPoints.value) || 1;
                inputPoints.value = v + 10;
            });
        }

        // Step navigation
        function setStep(step) {
            currentStep = step;
            for (let i = 1; i <= 4; i++) {
                const pane = document.getElementById('paneStep' + i);
                const ind = document.getElementById('stepIndicator' + i);
                if (!pane || !ind) continue;

                if (i === step) {
                    pane.classList.remove('d-none');
                    ind.classList.remove('opacity-50');
                    ind.classList.add('border-primary');
                    ind.classList.remove('border-secondary', 'border-success');
                    ind.querySelector('.badge').className = 'badge rounded-pill bg-primary me-1';
                } else if (i < step) {
                    pane.classList.add('d-none');
                    ind.classList.remove('opacity-50');
                    ind.classList.add('border-success');
                    ind.classList.remove('border-primary', 'border-secondary');
                    ind.querySelector('.badge').className = 'badge rounded-pill bg-success me-1';
                } else {
                    pane.classList.add('d-none');
                    ind.classList.add('opacity-50');
                    ind.classList.remove('border-primary', 'border-success');
                    ind.classList.add('border-secondary');
                    ind.querySelector('.badge').className = 'badge rounded-pill bg-secondary me-1';
                }
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Step 1 -> 2
        const btnGotoStep2 = document.getElementById('btnGotoStep2');
        if (btnGotoStep2) {
            btnGotoStep2.addEventListener('click', function() {
                const pts = parseFloat(inputPoints.value) || 0;
                if (pts <= 0) {
                    toast('Please specify at least 1 point.', 'warning');
                    return;
                }
                setStep(2);
            });
        }

        // Back to 1
        const btnBackToStep1 = document.getElementById('btnBackToStep1');
        if (btnBackToStep1) {
            btnBackToStep1.addEventListener('click', function() {
                setStep(1);
            });
        }

        // Method selection in Step 2
        document.querySelectorAll('.method-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.method-card').forEach(c => {
                    c.classList.remove('border-primary', 'bg-label-primary');
                    const badge = c.querySelector('.select-badge');
                    if (badge) {
                        badge.className = 'badge bg-secondary select-badge w-100 py-2';
                        badge.innerHTML = '<i class="ti tabler-circle me-1"></i>Select this Method';
                    }
                });

                this.classList.add('border-primary', 'bg-label-primary');
                const selBadge = this.querySelector('.select-badge');
                if (selBadge) {
                    selBadge.className = 'badge bg-primary select-badge w-100 py-2';
                    selBadge.innerHTML = '<i class="ti tabler-circle-check me-1"></i>Selected';
                }

				const rawEl = document.getElementById('inst-raw-' + this.dataset.id);
				const rawVal = (rawEl && rawEl.value) ? rawEl.value : (this.dataset.inst || '');
				const instText = decodeHtmlEntities(rawVal);

				selectedMethod = {
					id: this.dataset.id,
					name: this.dataset.name,
					rate: parseFloat(this.dataset.rate),
					currency: this.dataset.currency,
					min: parseInt(this.dataset.min),
					max: parseInt(this.dataset.max),
					instructions: instText
				};

				if (selectedMethodId) selectedMethodId.value = selectedMethod.id;
				const btn3 = document.getElementById('btnGotoStep3');
				if (btn3) btn3.disabled = false;
			});
		});

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

		// Dynamic script and style re-hydration engine for rich HTML/CSS/JS widgets
		function renderDynamicInstructions(container, htmlContent) {
			if (!container) return;
			let content = decodeHtmlEntities(htmlContent || '');
			if (!content || !content.trim()) {
				container.innerHTML = '<p class="text-muted fst-italic">No specific instructions. Please transfer the amount and upload receipt.</p>';
				return;
			}

			// If plain text with no HTML markup, preserve newlines cleanly
			if (!/<[a-z][\s\S]*>/i.test(content)) {
				content = '<div style="white-space: pre-wrap; line-height: 1.6;">' +
					content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') +
					'</div>';
			}

			// 1. Insert HTML markup and CSS styles
			container.innerHTML = content;

			// 2. Re-create and evaluate all <script> tags so interactive code executes
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

        // Step 2 -> 3
        const btnGotoStep3 = document.getElementById('btnGotoStep3');
        if (btnGotoStep3) {
            btnGotoStep3.addEventListener('click', function() {
                if (!selectedMethod) {
                    toast('Please select a payment method.', 'warning');
                    return;
                }

                const pts = parseFloat(inputPoints.value) || 0;
                const errAlert = document.getElementById('methodErrorAlert');

                if (pts < selectedMethod.min) {
                    if (errAlert) {
                        errAlert.textContent = 'Minimum points for ' + selectedMethod.name + ' is ' + selectedMethod.min + ' points.';
                        errAlert.classList.remove('d-none');
                    }
                    return;
                }
                if (selectedMethod.max > 0 && pts > selectedMethod.max) {
                    if (errAlert) {
                        errAlert.textContent = 'Maximum points for ' + selectedMethod.name + ' is ' + selectedMethod.max + ' points.';
                        errAlert.classList.remove('d-none');
                    }
                    return;
                }
                if (errAlert) errAlert.classList.add('d-none');

                // Populate summary in Step 3
                const total = (pts * selectedMethod.rate).toFixed(2);
                document.getElementById('summaryMethodName').textContent = selectedMethod.name;
                document.getElementById('summaryPoints').textContent = pts + ' credits';
                document.getElementById('summaryTotalCost').textContent = total + ' ' + selectedMethod.currency;

                const instEl = document.getElementById('instructionsHtmlContent');
                if (instEl) {
                    renderDynamicInstructions(instEl, selectedMethod.instructions);
                }

                setStep(3);
            });
        }

        // Back to 2
        const btnBackToStep2 = document.getElementById('btnBackToStep2');
        if (btnBackToStep2) {
            btnBackToStep2.addEventListener('click', function() {
                setStep(2);
            });
        }

        // Copy instructions
        const btnCopyInst = document.getElementById('btnCopyInstructions');
        if (btnCopyInst) {
            btnCopyInst.addEventListener('click', function() {
                const text = document.getElementById('instructionsHtmlContent').innerText;
                navigator.clipboard.writeText(text).then(() => toast('Instructions copied to clipboard!', 'success'));
            });
        }

        // Step 3 -> 4
        const btnGotoStep4 = document.getElementById('btnGotoStep4');
        if (btnGotoStep4) {
            btnGotoStep4.addEventListener('click', function() {
                setStep(4);
            });
        }

        // Back to 3
        const btnBackToStep3 = document.getElementById('btnBackToStep3');
        if (btnBackToStep3) {
            btnBackToStep3.addEventListener('click', function() {
                setStep(3);
            });
        }

        // File Preview
        const inputReceipt = document.getElementById('inputReceiptFile');
        if (inputReceipt) {
            inputReceipt.addEventListener('change', function() {
                const file = this.files[0];
                const previewWrap = document.getElementById('receiptPreviewWrap');
                const previewImg = document.getElementById('receiptPreviewImg');

                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewWrap.classList.remove('d-none');
                    };
                    reader.readAsDataURL(file);
                } else if (previewWrap) {
                    previewWrap.classList.add('d-none');
                }
            });
        }

        // Final Submission
        const buyForm = document.getElementById('buyPointsForm');
        if (buyForm) {
            buyForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const btn = document.getElementById('btnSubmitFinalOrder');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Submitting Request...';
                }

                const formData = new FormData(this);

                fetch('api?action=billing_submit_order', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        if (window.Swal) {
                            Swal.fire({
                                title: 'Order Submitted!',
                                text: 'Order #' + res.order_code + ' submitted successfully! Your distributor will review the receipt and credit your points.',
                                icon: 'success',
                                confirmButtonText: 'View My Orders'
                            }).then(function() {
                                window.location.href = 'billing_my_orders';
                            });
                        } else {
                            toast('Order #' + res.order_code + ' submitted successfully!', 'success');
                            setTimeout(() => {
                                window.location.href = 'billing_my_orders';
                            }, 1500);
                        }
                    } else {
                        toast(res.message || 'Error submitting order', 'error');
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="ti tabler-send me-1"></i> Submit Purchase Request';
                        }
                    }
                })
                .catch(() => {
                    toast('Network error while submitting order.', 'error');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti tabler-send me-1"></i> Submit Purchase Request';
                    }
                });
            });
        }
    });
})();
</script>
</body>
</html>
