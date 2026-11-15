import { afterEach, describe, expect, it } from 'vitest';

import dayjs from 'dayjs';

import {
  resetTransactionStore,
  useTransactionStore,
  type Transaction
} from './transactionStore';
import { resetSplitStore, useSplitStore } from './splitStore';
import { resetAccountStore, useAccountStore } from './accountStore';
import { AccountTypeType } from './accountTypeStore';

import { DateUtils } from '../utils/DateUtils';

describe('transactionStore', () => {
  it('should insert a new transaction', async () => {
    const transactionStore = useTransactionStore();

    const transaction: Transaction = {
      id: 0,
      description: '',
      date: '2024-01-01',
      timestampAdded: Date.now()
    };

    await transactionStore.insertTransaction(transaction);

    expect(await transactionStore.getSortedByDate()).to.deep.equal([
      transaction
    ]);
  });

  it('should not insert the same transaction twice', async () => {
    const transactionStore = useTransactionStore();

    const transaction: Transaction = {
      id: 0,
      description: '',
      date: '2024-01-01',
      timestampAdded: Date.now()
    };

    await transactionStore.insertTransaction(transaction);
    await transactionStore.insertTransaction(transaction);

    expect(await transactionStore.getSortedByDate()).to.deep.equal([
      transaction
    ]);
  });

  it('should return a transaction by id', async () => {
    const transactionStore = useTransactionStore();

    const transaction1: Transaction = {
      id: 0,
      description: '',
      date: '2024-01-01',
      timestampAdded: Date.now()
    };
    const transaction2: Transaction = {
      id: 1,
      description: '',
      date: '2024-01-01',
      timestampAdded: Date.now()
    };

    await transactionStore.insertTransactions([transaction1, transaction2]);

    expect(await transactionStore.getById(0)).to.deep.equal(transaction1);
  });

  it('should return transactions by account id', async () => {
    const transactionStore = useTransactionStore();
    const splitStore = useSplitStore();

    const accountStore = useAccountStore();
    accountStore.insertAccount({
      id: 0,
      bookId: 0,
      name: 'account0',
      description: '',
      currency: '',
      type: AccountTypeType.ASSET,
      stats: {},
      extraData: {}
    });
    accountStore.insertAccount({
      id: 1,
      bookId: 0,
      name: 'account1',
      description: '',
      currency: '',
      type: AccountTypeType.ASSET,
      stats: {},
      extraData: {}
    });

    const transaction1: Transaction = {
      id: 0,
      description: '',
      date: '2024-01-01',
      timestampAdded: Date.now()
    };
    const split1 = {
      id: 0,
      description: '',
      value: 12.345,
      convertRate: 1.0,
      transactionId: transaction1.id,
      destAccountId: 0
    };

    const transaction2: Transaction = {
      id: 1,
      description: '',
      date: '2024-01-01',
      timestampAdded: Date.now()
    };
    const split2 = {
      id: 1,
      description: '',
      value: 12.345,
      convertRate: 1.0,
      transactionId: transaction2.id,
      destAccountId: 1
    };

    await transactionStore.insertTransactions([transaction1, transaction2]);
    await splitStore.insertSplits([split1, split2]);

    expect(await transactionStore.getByAccountId(0)).to.deep.equal([
      transaction1
    ]);
  });

  it('should return transactions sorted by date', async () => {
    const transactionStore = useTransactionStore();

    const date = dayjs();

    const transaction1: Transaction = {
      id: 0,
      description: '',
      date: DateUtils.getDateStringForTransaction(date),
      timestampAdded: Date.now()
    };
    const transaction2: Transaction = {
      id: 1,
      description: '',
      date: DateUtils.getDateStringForTransaction(date.add(1, 'day')),
      timestampAdded: Date.now()
    };

    await transactionStore.insertTransactions([transaction1, transaction2]);

    expect(await transactionStore.getSortedByDate()).to.deep.equal([
      transaction2,
      transaction1
    ]);
  });

  it('should delete a transaction', async () => {
    const transactionStore = useTransactionStore();

    const transaction1: Transaction = {
      id: 0,
      description: '',
      date: '2024-01-01',
      timestampAdded: Date.now()
    };
    const transaction2: Transaction = {
      id: 1,
      description: '',
      date: '2024-01-01',
      timestampAdded: Date.now()
    };

    await transactionStore.insertTransactions([transaction1, transaction2]);
    await transactionStore.deleteTransaction(transaction1.id);

    expect(await transactionStore.getSortedByDate()).to.deep.equal([
      transaction2
    ]);
  });

  it('should move account values to the new date when a transaction date changes', async () => {
    const transactionStore = useTransactionStore();
    const splitStore = useSplitStore();
    const accountStore = useAccountStore();

    accountStore.insertAccount({
      id: 0,
      bookId: 0,
      name: 'account0',
      description: '',
      currency: '',
      type: AccountTypeType.ASSET,
      stats: {},
      extraData: {}
    });
    accountStore.insertAccount({
      id: 1,
      bookId: 0,
      name: 'account1',
      description: '',
      currency: '',
      type: AccountTypeType.ASSET,
      stats: {},
      extraData: {}
    });

    const transaction: Transaction = {
      id: 0,
      description: '',
      date: '2024-01-15',
      timestampAdded: Date.now()
    };
    const insertedTransaction =
      await transactionStore.insertTransaction(transaction);

    // Apply the values of the balanced two-split transaction at its old date.
    accountStore.addValue({
      accountId: 1,
      value: 100.0,
      date: dayjs('2024-01-15').toDate()
    });
    accountStore.addValue({
      accountId: 0,
      value: -100.0,
      date: dayjs('2024-01-15').toDate()
    });

    await splitStore.insertSplit({
      id: 0,
      description: '',
      value: 100.0,
      convertRate: 1.0,
      transactionId: transaction.id,
      destAccountId: 1
    });
    await splitStore.insertSplit({
      id: 1,
      description: '',
      value: -100.0,
      convertRate: 1.0,
      transactionId: transaction.id,
      destAccountId: 0
    });

    insertedTransaction.date = '2024-02-20';

    // The store adjusts the account values asynchronously.
    await new Promise((resolve) => setTimeout(resolve, 0));

    // The values were reversed at the old date and added at the new date.
    expect(accountStore.getValue({ accountId: 0, year: 2024, month: 1 })).toBe(
      0.0
    );
    expect(accountStore.getValue({ accountId: 0, year: 2024, month: 2 })).toBe(
      -100.0
    );
    expect(accountStore.getValue({ accountId: 1, year: 2024, month: 1 })).toBe(
      0.0
    );
    expect(accountStore.getValue({ accountId: 1, year: 2024, month: 2 })).toBe(
      100.0
    );
  });

  afterEach(() => {
    resetTransactionStore();
    resetSplitStore();
    resetAccountStore();
  });
});
