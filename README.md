1. enum income, expense, transfer
2. 


1. CRUD -> [Expense_Income_Account] Income Account, Expense Account ( Name, Description, enum [expense, income], category_id )
2. CRUD -> [upcommint_expence_income_account] title, description, income of expense account id , date, type [income/expense], attachment(multi)
3. Transaction name, category_id, from_account_id, to_account_id [both will foreign_Id], description , attachment multiple, send_total_amount, send_actual_amount, receive_total_amount, receive_actual_amount 
(Income Transaction -> select From account which will be expense income acoount, Normal Asset Account), 
(Expense Transaction From Account Asset Account, To Expense Income Account), 
(Transfar ASset to asset)

4. transaction_fees name, amount , enum [from , to], transaction_id (foreign id not constrant)


example stripe -> bangladesh bank normal transaction

both asset account transfer. 300$ out from stripe first fee 30$, 0.9$ -> 269.1$. entry the deposit amount 3500 tk -> 34995 tk with fees.