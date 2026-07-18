// ============================================================
//  Mô hình lớp hệ thống Website bán Laptop
//  Dùng cho Visual Paradigm > Instant Reverse (Java)
//  Sau khi reverse xong có thể xoá file này.
// ============================================================
import java.util.Date;

class User {
    private long id;
    private String name;
    private String email;
    private String password;
    private String role;
    private String phone;
    private String address;
    private int status;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
    public void DangKy() {}
    public void DangNhap() {}
    public void DatLaiMatKhau() {}
    public void CapNhatThongTin() {}
}

class Category {
    private int id;
    private String name;
    private int parent_id;
    private String slug;
    private String status;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
}

class Brand {
    private int id;
    private String name;
    private String slug;
    private String description;
    private int status;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
}

class Product {
    private int id;
    private int category_id;
    private int brand_id;
    private String name;
    private String slug;
    private String sku;
    private double price;
    private double sale_price;
    private int stock_quantity;
    private int low_stock_threshold;
    private String warranty;
    private int status;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
    public double TinhGiaCuoi() { return 0; }
    public boolean KiemTraTonKho() { return true; }
    public boolean CanhBaoSapHet() { return false; }
}

class ProductImage {
    private int id;
    private int product_id;
    private String image;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
}

class ProductSpec {
    private int id;
    private int product_id;
    private String cpu;
    private String ram;
    private String storage;
    private String gpu;
    private String screen;
    private String battery;
    private double weight;
    private String os;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
}

class Review {
    private int id;
    private int product_id;
    private int user_id;
    private int rating;
    private String comment;
    private int status;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
    public void Duyet() {}
}

class Cart {
    private int id;
    private int user_id;
    private int session_id;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
    public void ThemSanPham() {}
    public void XoaSanPham() {}
    public double TinhTong() { return 0; }
}

class CartItem {
    private int id;
    private int cart_id;
    private int product_id;
    private int quantity;
    private double price;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
    public double TinhThanhTien() { return 0; }
}

class Coupon {
    private int id;
    private String code;
    private String type;
    private double value;
    private double min_order_amount;
    private double max_discount_amount;
    private Date start_date;
    private Date end_date;
    private int use_limit;
    private int use_count;
    private String status;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
    public boolean KiemTraHopLe() { return true; }
    public double ApDungGiamGia() { return 0; }
}

class Order {
    private int id;
    private int user_id;
    private int coupon_id;
    private String order_code;
    private String customer_name;
    private String customer_email;
    private String customer_phone;
    private String shipping_address;
    private double subtotal;
    private double discount_amount;
    private double shipping_fee;
    private double total_amount;
    private String payment_method;
    private String payment_status;
    private String order_status;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
    public double TinhTongTien() { return 0; }
    public void CapNhatTrangThai() {}
    public void HuyDon() {}
}

class OrderItem {
    private int id;
    private int order_id;
    private int product_id;
    private String product_name;
    private double product_price;
    private int quantity;
    private double total_price;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
}

class ShippingZone {
    private int id;
    private String region;
    private String provinces;
    private double fee;
    private double free_threshold;
    private String estimate_days;
    private int status;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
    public double TinhPhiVanChuyen() { return 0; }
}

class ImportReceipt {
    private int id;
    private String code;
    private String supplier_name;
    private String reason;
    private String note;
    private double total_amount;
    private Date import_date;
    private String status;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
    public void ThemChiTiet() {}
    public void TaoSanPham() {}
    public void NhapKho() {}
    public double TinhTong() { return 0; }
}

class ImportReceiptItem {
    private int id;
    private int receipt_id;
    private int product_id;
    private int quantity;
    private double unit_price;
    private double subtotal;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
}

class ExportReceipt {
    private int id;
    private String code;
    private int order_id;
    private String reason;
    private String note;
    private Date export_date;
    private String status;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
    public void ThemChiTiet() {}
    public void XuatKho() {}
}

class ExportReceiptItem {
    private int id;
    private int receipt_id;
    private int product_id;
    private int quantity;
    private double unit_price;
    private double subtotal;

    public void Them() {}
    public void Sua() {}
    public void Xoa() {}
}

class StockTransaction {
    private int id;
    private int product_id;
    private String transaction_type;
    private int quantity_change;
    private int quantity_before;
    private int quantity_after;
    private int receipt_id;
    private String receipt_type;
    private String note;

    public void Them() {}
    public void Xem() {}
    public void GhiNhan() {}
}
