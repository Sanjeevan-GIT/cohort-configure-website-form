<?php if ( ! defined( 'ABSPATH' ) ) exit; // No direct access. ?>

<div class="cwf-tab-content" id="budget">
    <div class="cwf-tab-main">
        <div class="cwf-tab-top">
            <div class="cwf-selection-header">
                <span class="cwf-section-title">Budget</span>
                <span class="cwf-selected-value" id="selected-budget-value">Please select</span>
            </div>           

            <ul class="cwf-options" data-field="budget">
                <li data-value="< £1000">
                    <div class="cwf-option-row">                        
                        <div class="cwf-option-title">&lt; £1000</div>
                        <span class="cwf-option-plus">+</span>
                    </div>
                </li>
                <li data-value="£1000 - £3000">
                    <div class="cwf-option-row">                        
                        <div class="cwf-option-title">£1000 - £3000</div>
                        <span class="cwf-option-plus">+</span>
                    </div>
                </li>
                <li data-value="£3000 - £6000">
                    <div class="cwf-option-row">                        
                        <div class="cwf-option-title">£3000 - £6000</div>
                        <span class="cwf-option-plus">+</span>
                    </div>
                </li>
                <li data-value="£6000+">
                    <div class="cwf-option-row">                        
                        <div class="cwf-option-title">£6000+</div>
                        <span class="cwf-option-plus">+</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="cwf-tab-bottom">
            <button class="cwf-next" data-next="summary">Next: Summary</button>
        </div>
    </div>
</div>