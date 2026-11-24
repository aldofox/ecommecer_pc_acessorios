<?php
function renderCheckoutSteps(string $currentStep) {
    $steps = [
        'cart' => 'Carrinho',
        'checkout' => 'Checkout',
        'payment' => 'Pagamento',
        'done' => 'Conclusão'
    ];

    echo '<div class="checkout-steps">';
    foreach ($steps as $key => $label) {
        $class = '';
        if ($key === $currentStep) {
            $class = 'active';
        } elseif (array_search($key, array_keys($steps)) < array_search($currentStep, array_keys($steps))) {
            $class = 'completed';
        }
        echo "<div class='step $class'>$label</div>";
    }
    echo '</div>';
}
