# JvH Isotope Stock Bundle

Isotope Stock (Jan van Haasteren Specifics)

This adds a simple screen for doing bookings in the stock module.

This extension is developed specifically for Jan van Haasteren. Feel free to re-use

## Configuration

The following configuration can be adjusted to your needs and added to your config.yml file.

```yaml 
jv_h_isotope_stock:
  sales_account_id: 3
  pre_order_sales_account_id: 10
  mass_booking_config:
    - {label: 'Ontvangst Producten', credit_id: 5, debit_id: 2, type: 4}
    - {label: 'Plaatsen inkoop pre-order', credit_id: 8, debit_id: 9, type: 3}
    - {label: 'Ontvangst inkoop Pre-Order', credit_id: 9, debit_id: 11, type: 4}
    - {label: 'Vrijgeven inkoop Pre-Order', credit_id: 11, debit_id: 2, is_pre_order_delivery: true, type: 0}

```

## Requirements

* Contao > 4.9
* Isotope

## Contributions

Contributions to this bundle are more than welcome. Please submit your contributions as a merge request.

## License

AGPL 3.0