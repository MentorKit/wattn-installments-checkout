// SimplyLearn Installments – Calculator functionality
(function() {
    'use strict';
    
    let initialized = false;
    let calculatorInstance = null;
    
    // Make updateCalculator globally accessible
    window.sliUpdateCalculator = function() {
        if (calculatorInstance && calculatorInstance.updateCalculator) {
            console.log('SimplyLearn Installments: Global updateCalculator called');
            calculatorInstance.updateCalculator();
        }
    };
    
    // Wait for DOM to be ready
    function tryInit() {
        if (initialized) {
            console.log('SimplyLearn Installments: Already initialized, skipping...');
            return;
        }
        console.log('SimplyLearn Installments: Trying to initialize calculator...');
        
        // Add a small delay to ensure other scripts have finished
        setTimeout(() => {
            initCalculator();
        }, 100);
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
        
        // Use event delegation to handle dynamic content
        console.log('SimplyLearn Installments: Setting up event delegation for plan changes');
        
        // Listen for clicks on the document and check if it's a plan radio button
        document.addEventListener('click', function(event) {
            if (event.target && event.target.name === 'sli_plan' && event.target.type === 'radio') {
                console.log('SimplyLearn Installments: Plan clicked:', event.target.value);
                setTimeout(() => {
                    updateCalculator();
                }, 10);
            }
        });
        
        // Also listen for change events with delegation
        document.addEventListener('change', function(event) {
            if (event.target && event.target.name === 'sli_plan' && event.target.type === 'radio') {
                console.log('SimplyLearn Installments: Plan changed to:', event.target.value);
                updateCalculator();
            }
        });
        
        // Also add direct listeners as backup
        const planInputs = document.querySelectorAll('input[name="sli_plan"]');
        console.log('SimplyLearn Installments: Found', planInputs.length, 'plan inputs for direct listeners');
        planInputs.forEach(function(input) {
            console.log('SimplyLearn Installments: Adding direct event listener to', input.value);
            input.addEventListener('change', function() {
                console.log('SimplyLearn Installments: Direct listener - Plan changed to', input.value);
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
        
        // Add visibility protection - check if calculator gets hidden
        const protectVisibility = () => {
            if (calcContainer && calcContainer.style.display === 'none') {
                console.log('SimplyLearn Installments: Calculator was hidden, restoring visibility');
                calcContainer.style.display = 'block';
                calcContainer.style.visibility = 'visible';
            }
            if (monthlyDisplay && monthlyDisplay.style.display === 'none') {
                console.log('SimplyLearn Installments: Monthly display was hidden, restoring');
                monthlyDisplay.style.display = 'inline';
                monthlyDisplay.style.visibility = 'visible';
            }
            if (creditDisplay && creditDisplay.style.display === 'none') {
                console.log('SimplyLearn Installments: Credit display was hidden, restoring');
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
                        console.log('SimplyLearn Installments: Re-attached listener - Plan changed to', input.value);
                        updateCalculator();
                    });
                });
            }
            
            visibilityChecks++;
            if (visibilityChecks >= 20) { // 10 seconds
                clearInterval(visibilityInterval);
                console.log('SimplyLearn Installments: Stopped visibility protection');
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
        console.log('SimplyLearn Installments: Calculator initialized successfully');
    }
})();