# User Management Endpoints

Both endpoints require an authenticated **admin** (`admin = 1`) token.
Staff (`admin = 2`) are blocked by the `RestrictStaff` middleware.

**Header:** `Authorization: Bearer <token>`

| Action      | Method | URL                       |
| ----------- | ------ | ------------------------- |
| List users  | POST   | `/api/users`              |
| Create user | POST   | `/api/store/user`         |
| Update user | POST   | `/api/update/user/{id}`   |
| Delete user | POST   | `/api/delete/user/{id}`   |

## Roles

The `role` field maps to the `admin` column:

| role       | admin value |
| ---------- | ----------- |
| `customer` | `0`         |
| `admin`    | `1`         |
| `staff`    | `2`         |
| `referrer` | `null`      |

---

## Create user — `POST /api/store/user`

### Staff
```json
{
  "name": "Jane Doe",
  "email": "jane@hayzee.com",
  "phone": "08030000001",
  "address": "12 Marina, Lagos",
  "password": "secret123",
  "role": "staff"
}
```

### Admin
```json
{
  "name": "Admin User",
  "email": "admin@hayzee.com",
  "phone": "08030000002",
  "password": "secret123",
  "role": "admin"
}
```

### Customer (`address` optional)
```json
{
  "name": "John Buyer",
  "email": "john@gmail.com",
  "phone": "08030000003",
  "password": "secret123",
  "role": "customer"
}
```

### Referrer (`admin` saved as `null`)
```json
{
  "name": "Ref Partner",
  "email": "ref@gmail.com",
  "phone": "08030000004",
  "password": "secret123",
  "role": "referrer"
}
```

**Response** → `201`
```json
{
  "user": {
    "id": 14,
    "name": "Jane Doe",
    "email": "jane@hayzee.com",
    "phone": "08030000001",
    "address": "12 Marina, Lagos",
    "admin": 2
  }
}
```

---

## Update user — `POST /api/update/user/{id}`

All fields optional. Omit `password` to keep the current one.

### Change role + details
```json
{
  "name": "Jane Updated",
  "email": "jane@hayzee.com",
  "phone": "08030000001",
  "address": "New Address",
  "role": "admin"
}
```

### Password only
```json
{
  "password": "newsecret456"
}
```

**Response** → `200`
```json
{
  "user": {
    "id": 14,
    "name": "Jane Updated",
    "email": "jane@hayzee.com",
    "phone": "08030000001",
    "address": "New Address",
    "admin": 1
  }
}
```

---

## List users — `POST /api/users`

Optional filters:

```json
{
  "search": "jane",
  "role": "staff",
  "rows": 20,
  "page": 1
}
```

`role` accepts a role name (`customer`, `admin`, `staff`, `referrer`) or the
raw numeric `admin` value. Omit it to return all users.

## Validation errors → `422`
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```
