import { afterEach, describe, expect, it, vi } from 'vitest';

import {
  resetTransactionService,
  useTransactionService
} from './transactionService';
import {
  resetTransactionStore,
  useTransactionStore,
  type Transaction
} from '../stores/transactionStore';
import { resetSplitStore, useSplitStore } from '../stores/splitStore';
import { resetAccountStore, useAccountStore } from '../stores/accountStore';
import { AccountTypeType } from '../stores/accountTypeStore';

const axiosMocks = vi.hoisted(() => ({
  delete: vi.fn().mockResolvedValue({})
}));

vi.mock('@nextcloud/axios', () => {
  return {
    default: axiosMocks
  };
});

vi.mock('@nextcloud/router', () => {
  return {
    generateUrl: (url: string): string => url
  };
});

describe('transactionService', () => {
  it('should delete a transaction with its splits and reverse the account values', async () => {
    const transactionService = useTransactionService();
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
      description: 'groceries',
      date: '2024-01-15',
      timestampAdded: Date.now()
    };
    const insertedTransaction =
      await transactionStore.insertTransaction(transaction);

    // The values of the balanced two-split transaction, applied at its date.
    accountStore.addValue({
      accountId: 1,
      value: 100.0,
      date: new Date(2024, 0, 15)
    });
    accountStore.addValue({
      accountId: 0,
      value: -100.0,
      date: new Date(2024, 0, 15)
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

    await transactionService.deleteTransaction(insertedTransaction);

    expect(axiosMocks.delete).toHaveBeenCalledWith('apps/money/transactions/0');

    expect(await transactionStore.getById(transaction.id)).toBeNull();

    expect(await splitStore.getByTransactionId(transaction.id)).to.deep.equal(
      []
    );

    expect(accountStore.getValue({ accountId: 0, year: 2024, month: 1 })).toBe(
      0.0
    );
    expect(accountStore.getValue({ accountId: 1, year: 2024, month: 1 })).toBe(
      0.0
    );
  });

  afterEach(() => {
    resetTransactionService();
    resetTransactionStore();
    resetSplitStore();
    resetAccountStore();
    vi.clearAllMocks();
  });
});
