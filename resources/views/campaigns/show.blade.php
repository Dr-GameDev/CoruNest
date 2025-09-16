.donation-form {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 6rem;
        }

        .donation-amounts {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .amount-btn {
            padding: 1rem;
            border: 2px solid var(--gray-300);
            background: white;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            text-align: center;
        }

        .amount-btn.selected {
            border-color: var(--primary-600);
            background: var(--primary-50);
            color: var(--primary-600);
        }

        .custom-amount {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .custom-amount:focus {
            outline: none;
            border-color: var(--primary-600);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-600);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .payment-method {
            padding: 1rem;
            border: 2px solid var(--gray-300);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            font-weight: 600;
        }

        .payment-method.selected {
            border-color: var(--primary-600);
            background: var(--primary-50);
            color: var(--primary-600);
        }