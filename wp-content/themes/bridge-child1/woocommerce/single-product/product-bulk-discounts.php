<?php

    if(have_rows('product-bulk-discounts')){
        echo '<div class="pbd">';
            echo '<h2>Bulk Purchasing Discount</h2>';
            echo '<table class="table--pbd">';
            while(have_rows('product-bulk-discounts')){ the_row();

                echo '<tr>';
                    echo '<th>'.get_sub_field('label').'</th>';
                    echo '<td>'.get_sub_field('value').'</td>';
                echo '</tr>';

            } // endwhile;
            echo '</table>';
            echo '<p>Your bulk purchasing discount will be added at checkout</p>';
        echo '</div>';
    } // endif;

?>
