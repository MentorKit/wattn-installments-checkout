// SimplyLearn Installments – Calculator functionality
(function() {
    'use strict';
    
    let initialized = false;
    let calculatorInstance = null;
    
    // Make updateCalculator globally accessible
    window.sliUpdateCalculator = function() {
        if (calculatorInstance && calculatorInstance.updateCalculator) {
            calculatorInstance.updateCalculator();
        }
    };
    
    // Wait for DOM to be ready
    function tryInit() {
        if (initialized) {
            return;
        }
        
        // Add a small delay to ensure other scripts have finished
        setTimeout(() => {
            initCalculator();
        }, 100);
    }
    
    // Try multiple events to ensure we catch the right timing
    document.addEventListener('DOMContentLoaded', tryInit);
    document.addEventListener('load', tryInit);
    
    // Also try immediately if DOM is already ready
    if (document.readyState !== 'loading') {
        tryInit();
    }
    
    function initCalculator() {
        const calcContainer = document.getElementById('sli-calc');
        if (!calcContainer) {
            return;
        }
        
        const monthlyDisplay = document.getElementById('sli-monthly');
        const creditDisplay = document.getElementById('sli-credit');
        
        if (!monthlyDisplay || !creditDisplay) {
            return;
        }
        
        // Get data from container
        const basis = parseFloat(calcContainer.getAttribute('data-basis') || '0');
        const apr = parseFloat(calcContainer.getAttribute('data-apr') || '0');
        const fee = parseFloat(calcContainer.getAttribute('data-fee') || '0');
        const decimals = parseInt(calcContainer.getAttribute('data-decimals') || '2');
        const currency = calcContainer.getAttribute('data-curr') || '';
        
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
            const selectedPlan = document.querySelector('input[name="sli_plan"]:checked');
            if (!selectedPlan) {
                monthlyDisplay.textContent = '—';
                creditDisplay.textContent = '—';
                return;
            }
            
            const months = codeToMonths(selectedPlan.value);
            const monthlyBase = calculateMonthlyPayment(basis, apr, months);
            const monthlyWithFee = monthlyBase + fee;
            const totalPayments = monthlyWithFee * months;
            const creditCost = Math.max(0, totalPayments - basis);
            
            const monthlyText = currency + ' ' + formatNumber(monthlyWithFee, decimals);
            const creditText = currency + ' ' + formatNumber(creditCost, decimals);
            
            // Set the text content
            monthlyDisplay.textContent = monthlyText;
            creditDisplay.textContent = creditText;
            
            // Force visibility with inline styles to prevent interference
            monthlyDisplay.style.display = 'inline';
            monthlyDisplay.style.visibility = 'visible';
            monthlyDisplay.style.opacity = '1';
            monthlyDisplay.style.color = '#059669';
            monthlyDisplay.style.fontWeight = 'bold';
            
            creditDisplay.style.display = 'inline';
            creditDisplay.style.visibility = 'visible';
            creditDisplay.style.opacity = '1';
            creditDisplay.style.color = '#059669';
            creditDisplay.style.fontWeight = 'bold';
        }
        
        // Use event delegation to handle dynamic content
        document.addEventListener('click', function(event) {
            if (event.target && event.target.name === 'sli_plan' && event.target.type === 'radio') {
                setTimeout(() => {
                    updateCalculator();
                }, 10);
            }
        });
        
        // Also listen for change events with delegation
        document.addEventListener('change', function(event) {
            if (event.target && event.target.name === 'sli_plan' && event.target.type === 'radio') {
                updateCalculator();
            }
        });
        
        // Also add direct listeners as backup
        const planInputs = document.querySelectorAll('input[name="sli_plan"]');
        planInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                updateCalculator();
            });
        });
        
        // Set default selection to 36 months
        const defaultPlan = document.querySelector('input[name="sli_plan"][value="36m"]');
        if (defaultPlan) {
            defaultPlan.checked = true;
        }
        
        // Initial calculation
        updateCalculator();
        
        // Add visibility protection - check if calculator gets hidden
        const protectVisibility = () => {
            if (calcContainer && calcContainer.style.display === 'none') {
                calcContainer.style.display = 'block';
                calcContainer.style.visibility = 'visible';
            }
            if (monthlyDisplay && monthlyDisplay.style.display === 'none') {
                monthlyDisplay.style.display = 'inline';
                monthlyDisplay.style.visibility = 'visible';
            }
            if (creditDisplay && creditDisplay.style.display === 'none') {
                creditDisplay.style.display = 'inline';
                creditDisplay.style.visibility = 'visible';
            }
        };
        
        // Check visibility and re-attach listeners every 500ms for the first 10 seconds
        let visibilityChecks = 0;
        const visibilityInterval = setInterval(() => {
            protectVisibility();
            
            // Re-attach direct listeners in case DOM was updated
            const currentPlanInputs = document.querySelectorAll('input[name="sli_plan"]');
            if (currentPlanInputs.length > 0) {
                currentPlanInputs.forEach(function(input) {
                    // Remove existing listeners to avoid duplicates
                    input.removeEventListener('change', updateCalculator);
                    // Add new listener
                    input.addEventListener('change', function() {
                        updateCalculator();
                    });
                });
            }
            
            visibilityChecks++;
            if (visibilityChecks >= 20) { // 10 seconds
                clearInterval(visibilityInterval);
            }
        }, 500);
        
        // Also check on any DOM changes
        const observer = new MutationObserver(protectVisibility);
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Store the calculator instance and updateCalculator function
        calculatorInstance = {
            updateCalculator: updateCalculator,
            calcContainer: calcContainer,
            monthlyDisplay: monthlyDisplay,
            creditDisplay: creditDisplay
        };
        
        // Mark as initialized
        initialized = true;
    }
})();