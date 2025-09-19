// SimplyLearn Installments – Calculator functionality
(function() {
    'use strict';
    
    let initialized = false;
    
    // Wait for DOM to be ready
    function tryInit() {
        if (initialized) {
            console.log('SimplyLearn Installments: Already initialized, skipping...');
            return;
        }
        console.log('SimplyLearn Installments: Trying to initialize calculator...');
        initCalculator();
    }
    
    // Try multiple events to ensure we catch the right timing
    document.addEventListener('DOMContentLoaded', tryInit);
    document.addEventListener('load', tryInit);
    
    // Also try immediately if DOM is already ready
    if (document.readyState === 'loading') {
        console.log('SimplyLearn Installments: DOM still loading, waiting...');
    } else {
        console.log('SimplyLearn Installments: DOM already ready, initializing immediately...');
        tryInit();
    }
    
    function initCalculator() {
        console.log('SimplyLearn Installments: initCalculator called');
        const calcContainer = document.getElementById('sli-calc');
        if (!calcContainer) {
            console.log('SimplyLearn Installments: Calculator container not found');
            return;
        }
        console.log('SimplyLearn Installments: Calculator container found');
        
        const monthlyDisplay = document.getElementById('sli-monthly');
        const creditDisplay = document.getElementById('sli-credit');
        
        if (!monthlyDisplay || !creditDisplay) {
            console.log('SimplyLearn Installments: Monthly or credit display elements not found');
            return;
        }
        console.log('SimplyLearn Installments: Display elements found');
        
        // Get data from container
        const basis = parseFloat(calcContainer.getAttribute('data-basis') || '0');
        const apr = parseFloat(calcContainer.getAttribute('data-apr') || '0');
        const fee = parseFloat(calcContainer.getAttribute('data-fee') || '0');
        const decimals = parseInt(calcContainer.getAttribute('data-decimals') || '2');
        const currency = calcContainer.getAttribute('data-curr') || '';
        
        console.log('SimplyLearn Installments: Data loaded', {
            basis: basis,
            apr: apr,
            fee: fee,
            decimals: decimals,
            currency: currency
        });
        
        // Amortization calculation
        function calculateMonthlyPayment(principal, aprPercent, months) {
            if (months <= 0) return 0;
            const r = (aprPercent / 100) / 12.0;
            if (r <= 0) {
                return principal / months;
            }
            const pow = Math.pow(1 + r, months);
            return principal * (r * pow) / (pow - 1);
        }
        
        // Convert plan code to months
        function codeToMonths(code) {
            switch(code) {
                case '6m': return 6;
                case '12m': return 12;
                case '24m': return 24;
                case '36m': return 36;
                default: return 6;
            }
        }
        
        // Format number with proper decimals
        function formatNumber(num, decimals) {
            return Number(num).toFixed(decimals);
        }
        
        // Update calculator display
        function updateCalculator() {
            console.log('SimplyLearn Installments: updateCalculator called');
            const selectedPlan = document.querySelector('input[name="sli_plan"]:checked');
            if (!selectedPlan) {
                console.log('SimplyLearn Installments: No plan selected');
                monthlyDisplay.textContent = '—';
                creditDisplay.textContent = '—';
                return;
            }
            
            console.log('SimplyLearn Installments: Selected plan:', selectedPlan.value);
            const months = codeToMonths(selectedPlan.value);
            const monthlyBase = calculateMonthlyPayment(basis, apr, months);
            const monthlyWithFee = monthlyBase + fee;
            const totalPayments = monthlyWithFee * months;
            const creditCost = Math.max(0, totalPayments - basis);
            
            console.log('SimplyLearn Installments: Calculations:', {
                months: months,
                monthlyBase: monthlyBase,
                monthlyWithFee: monthlyWithFee,
                totalPayments: totalPayments,
                creditCost: creditCost
            });
            
            const monthlyText = currency + ' ' + formatNumber(monthlyWithFee, decimals);
            const creditText = currency + ' ' + formatNumber(creditCost, decimals);
            
            console.log('SimplyLearn Installments: Setting display text:', {
                monthly: monthlyText,
                credit: creditText
            });
            
            monthlyDisplay.textContent = monthlyText;
            creditDisplay.textContent = creditText;
        }
        
        // Listen for plan changes
        const planInputs = document.querySelectorAll('input[name="sli_plan"]');
        console.log('SimplyLearn Installments: Found', planInputs.length, 'plan inputs');
        planInputs.forEach(function(input) {
            console.log('SimplyLearn Installments: Adding event listener to', input.value);
            input.addEventListener('change', function() {
                console.log('SimplyLearn Installments: Plan changed to', input.value);
                updateCalculator();
            });
        });
        
        // Set default selection to 36 months
        const defaultPlan = document.querySelector('input[name="sli_plan"][value="36m"]');
        if (defaultPlan) {
            console.log('SimplyLearn Installments: Setting default to 36 months');
            defaultPlan.checked = true;
        } else {
            console.log('SimplyLearn Installments: Default 36m plan not found');
        }
        
        // Initial calculation
        console.log('SimplyLearn Installments: Running initial calculation');
        updateCalculator();
        
        // Mark as initialized
        initialized = true;
        console.log('SimplyLearn Installments: Calculator initialized successfully');
    }
})();