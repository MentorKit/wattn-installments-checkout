// SimplyLearn Installments – frontend calculator
(function() {
    'use strict';
    
    console.log('SLI: JavaScript loaded');
    
    function initCalculator() {
        console.log('SLI: initCalculator called');
        
        const calcDiv = document.getElementById('sli-calc');
        console.log('SLI: calcDiv found:', calcDiv);
        if (!calcDiv) {
            console.log('SLI: No calcDiv found, will retry');
            return false;
        }
        
        // Get calculation parameters from data attributes
        const basis = parseFloat(calcDiv.dataset.basis) || 0;
        const apr = parseFloat(calcDiv.dataset.apr) || 0;
        const fee = parseFloat(calcDiv.dataset.fee) || 0;
        const decimals = parseInt(calcDiv.dataset.decimals) || 2;
        const currency = calcDiv.dataset.curr || 'NOK';
        
        console.log('SLI: Parameters:', { basis, apr, fee, decimals, currency });
        
        // Get display elements
        const monthlySpan = document.getElementById('sli-monthly');
        const creditSpan = document.getElementById('sli-credit');
        const totalSpan = document.getElementById('sli-total');
        
        console.log('SLI: Spans found:', { monthlySpan, creditSpan, totalSpan });
        if (!monthlySpan || !creditSpan || !totalSpan) {
            console.log('SLI: Missing spans, exiting');
            return;
        }
        
        // Watch for changes to the spans to see if something is overriding them
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' || mutation.type === 'characterData') {
                    console.log('SLI: Span content changed by external script:', {
                        target: mutation.target,
                        oldValue: mutation.oldValue,
                        newValue: mutation.target.textContent
                    });
                }
            });
        });
        
        observer.observe(monthlySpan, { 
            childList: true, 
            characterData: true, 
            subtree: true,
            characterDataOldValue: true
        });
        observer.observe(creditSpan, { 
            childList: true, 
            characterData: true, 
            subtree: true,
            characterDataOldValue: true
        });
        observer.observe(totalSpan, { 
            childList: true, 
            characterData: true, 
            subtree: true,
            characterDataOldValue: true
        });
        
        // Month mapping
        const monthMap = {
            '6m': 6,
            '12m': 12,
            '24m': 24,
            '36m': 36
        };
        
        // Amortizing loan calculation (matches PHP calc_monthly method)
        function calcMonthly(principal, aprPercent, months) {
            if (months <= 0) return 0;
            
            const r = Math.max(0, aprPercent) / 100 / 12;
            if (r <= 0) {
                return principal / months;
            }
            
            const pow = Math.pow(1 + r, months);
            return principal * (r * pow) / (pow - 1);
        }
        
        // Format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('nb-NO', {
                style: 'currency',
                currency: 'NOK',
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(amount);
        }
        
        // Calculate and display estimates
        function updateEstimates() {
            console.log('SLI: updateEstimates called');
            
            const selectedPlan = document.querySelector('input[name="sli_plan"]:checked');
            console.log('SLI: selectedPlan:', selectedPlan);
            
            if (!selectedPlan) {
                console.log('SLI: No plan selected, showing dashes');
                monthlySpan.textContent = '—';
                creditSpan.textContent = '—';
                totalSpan.textContent = '—';
                return;
            }
            
            const months = monthMap[selectedPlan.value];
            console.log('SLI: months for', selectedPlan.value, ':', months);
            
            if (!months) {
                console.log('SLI: Invalid months, showing dashes');
                monthlySpan.textContent = '—';
                creditSpan.textContent = '—';
                totalSpan.textContent = '—';
                return;
            }
            
            // Calculate monthly payment (matches PHP logic)
            const monthlyBase = calcMonthly(basis, apr, months);
            const monthlyTotal = Math.round((monthlyBase + fee) * Math.pow(10, decimals)) / Math.pow(10, decimals);
            const totalCreditCost = Math.max(0, Math.round((monthlyTotal * months - basis) * Math.pow(10, decimals)) / Math.pow(10, decimals));
            const totalCost = Math.round((basis + totalCreditCost) * Math.pow(10, decimals)) / Math.pow(10, decimals);
            
            console.log('SLI: Calculations:', { monthlyBase, monthlyTotal, totalCreditCost, totalCost });
            
            // Update display - try both methods
            const monthlyText = formatCurrency(monthlyTotal);
            const creditText = formatCurrency(totalCreditCost);
            const totalText = formatCurrency(totalCost);
            
            monthlySpan.textContent = monthlyText;
            creditSpan.textContent = creditText;
            totalSpan.textContent = totalText;
            
            // Also try innerHTML as backup
            monthlySpan.innerHTML = monthlyText;
            creditSpan.innerHTML = creditText;
            totalSpan.innerHTML = totalText;
            
            // Add visual debugging
            monthlySpan.style.backgroundColor = '#ffffcc';
            creditSpan.style.backgroundColor = '#ffffcc';
            totalSpan.style.backgroundColor = '#ffffcc';
            
            console.log('SLI: Updated spans:', { 
                monthly: monthlySpan.textContent, 
                credit: creditSpan.textContent,
                total: totalSpan.textContent
            });
            
            // Check if elements are visible
            console.log('SLI: Element visibility:', {
                monthlyVisible: monthlySpan.offsetParent !== null,
                creditVisible: creditSpan.offsetParent !== null,
                totalVisible: totalSpan.offsetParent !== null,
                monthlyDisplay: getComputedStyle(monthlySpan).display,
                creditDisplay: getComputedStyle(creditSpan).display,
                totalDisplay: getComputedStyle(totalSpan).display,
                monthlyWidth: monthlySpan.offsetWidth,
                creditWidth: creditSpan.offsetWidth,
                totalWidth: totalSpan.offsetWidth
            });
            
            // Force a visual update and check if values stick
            setTimeout(function() {
                console.log('SLI: After timeout - spans content:', {
                    monthly: monthlySpan.textContent,
                    credit: creditSpan.textContent,
                    total: totalSpan.textContent,
                    monthlyHTML: monthlySpan.innerHTML,
                    creditHTML: creditSpan.innerHTML,
                    totalHTML: totalSpan.innerHTML
                });
            }, 100);
        }
        
        // Listen for radio button changes
        const radioButtons = document.querySelectorAll('input[name="sli_plan"]');
        console.log('SLI: Found radio buttons:', radioButtons.length);
        
        radioButtons.forEach(function(radio, index) {
            console.log('SLI: Adding listener to radio', index, radio.value);
            radio.addEventListener('change', function() {
                console.log('SLI: Radio changed to:', this.value);
                updateEstimates();
            });
        });
        
        // Auto-select 36-month option if no plan is selected
        const selectedPlan = document.querySelector('input[name="sli_plan"]:checked');
        if (!selectedPlan) {
            console.log('SLI: No plan selected, auto-selecting 36m');
            const plan36m = document.querySelector('input[name="sli_plan"][value="36m"]');
            if (plan36m) {
                plan36m.checked = true;
                console.log('SLI: 36m plan selected');
            }
        }
        
        // Initial calculation if a plan is already selected
        console.log('SLI: Running initial calculation');
        updateEstimates();
        
        return true;
    }
    
    // Try to initialize immediately
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('SLI: DOM loaded, trying init');
            if (!initCalculator()) {
                // Retry after a short delay
                setTimeout(function() {
                    console.log('SLI: Retrying init after delay');
                    initCalculator();
                }, 1000);
            }
        });
    } else {
        console.log('SLI: DOM already loaded, trying init');
        if (!initCalculator()) {
            // Retry after a short delay
            setTimeout(function() {
                console.log('SLI: Retrying init after delay');
                initCalculator();
            }, 1000);
        }
    }
    
    // Also try on checkout update events (WooCommerce dynamic loading)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('updated_checkout', function() {
            console.log('SLI: Checkout updated, trying init');
            initCalculator();
        });
    }
    
    // Fallback: try periodically for a few seconds
    let retryCount = 0;
    const maxRetries = 10;
    const retryInterval = setInterval(function() {
        retryCount++;
        console.log('SLI: Periodic retry', retryCount);
        
        if (initCalculator() || retryCount >= maxRetries) {
            clearInterval(retryInterval);
            if (retryCount >= maxRetries) {
                console.log('SLI: Max retries reached, giving up');
            }
        }
    }, 500);
})();