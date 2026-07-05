import { describe, it, expect } from 'vitest';
import { formatNumber, formatMoney, parseNumber } from './formatters';

describe('formatNumber', () => {
  it('devuelve string vacío para valores nulos/vacíos', () => {
    expect(formatNumber(null)).toBe('');
    expect(formatNumber(undefined)).toBe('');
    expect(formatNumber('')).toBe('');
  });

  it('devuelve string vacío para valores no numéricos', () => {
    expect(formatNumber('abc')).toBe('');
  });

  it('formatea enteros con separador de miles', () => {
    expect(formatNumber(1000)).toBe('1.000');
    expect(formatNumber(1234567)).toBe('1.234.567');
  });

  it('respeta la cantidad de decimales solicitada', () => {
    expect(formatNumber(1234.5, 2)).toBe('1.234,50');
  });

  it('con hideRoundDecimals oculta los decimales de un entero', () => {
    expect(formatNumber(1234, 2, true)).toBe('1.234');
  });

  it('acepta strings con coma decimal', () => {
    expect(formatNumber('1234,5', 2)).toBe('1.234,50');
  });
});

describe('formatMoney', () => {
  it('formatea entero sin decimales', () => {
    expect(formatMoney(1234)).toBe('1.234');
  });

  it('formatea con dos decimales cuando no es entero', () => {
    expect(formatMoney(1234.5)).toBe('1.234,50');
  });

  it('acepta strings numéricos', () => {
    expect(formatMoney('1500.75')).toBe('1.500,75');
  });
});

describe('parseNumber', () => {
  it('devuelve null para valores vacíos', () => {
    expect(parseNumber('')).toBe(null);
    expect(parseNumber(null)).toBe(null);
    expect(parseNumber(undefined)).toBe(null);
  });

  it('conserva el cero', () => {
    expect(parseNumber(0)).toBe(0);
  });

  it('limpia separadores de miles y coma decimal', () => {
    expect(parseNumber('1.000')).toBe(1000);
    expect(parseNumber('1.234,50')).toBe(1234.5);
  });

  it('devuelve null para texto no numérico', () => {
    expect(parseNumber('abc')).toBe(null);
  });
});
